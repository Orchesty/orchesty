<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Webhook\Provider;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.10.17
 * Time: 11:58
 */

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use CleverConnectors\AppBundle\Utils\WebhookUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class ApiWebhookProvider
 *
 * @package CleverConnectors\AppBundle\Model\Webhook\Provider
 */
class ApiWebhookProvider implements WebhookProviderInterface, LoggerAwareInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ObjectRepository|WebhookRepository
     */
    private $webhookRepository;

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    private $systemRepository;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $domain;

    /**
     * ApiWebhookProvider constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     * @param string               $domain
     */
    function __construct(DocumentManager $dm, CurlManagerInterface $curl, string $domain)
    {
        $this->dm                = $dm;
        $this->webhookRepository = $dm->getRepository(Webhook::class);
        $this->systemRepository  = $dm->getRepository(SystemInstall::class);
        $this->curl              = $curl;
        $this->logger            = new NullLogger();
        $this->domain            = $domain;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return ApiWebhookProvider
     */
    public function setLogger(LoggerInterface $logger): ApiWebhookProvider
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     * @param bool                   $isUpdate
     */
    public function subscribe(WebhookSystemInterface $system, string $userId, string $token, $isUpdate = FALSE): void
    {
        $systemInstall = $this->getSystemInstall($system->getKey(), $userId);
        $requester     = $system->getSubscribeRequester($systemInstall);

        /** @var WebhookSubscribes $sub */
        foreach ($system->getWebhookSubscribes() as $sub) {
            if (!$isUpdate && $this->isSkippable($userId, $sub, $systemInstall)) {
                continue;
            }

            try {
                $requestDto  = $requester->getRequestDto([
                    RequesterInterface::OBJECT      => $sub,
                    RequesterInterface::WEBHOOK_URL => $this->getWebhookUrl($systemInstall, $sub),
                ]);
                $responseDto = $this->curl->send($requestDto);
                $id          = $requester->processResponse($responseDto, $systemInstall);
            } catch (Exception $e) {
                $this->logger->error(sprintf('Webhook (nodeName, topologyName, system) [%s, %s, %s] failed to subscribe.',
                    $sub->getNodeName(), $sub->getTopologyName(), $system->getKey()), ['exception' => $e]);
                $id = NULL;
            }

            $this->createWebhook($userId, $sub, $system->getKey(), $id);
        }
        $this->dm->flush();
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     */
    public function unsubscribe(WebhookSystemInterface $system, string $userId): void
    {
        $systemInstall = $this->getSystemInstall($system->getKey(), $userId);
        $requester     = $system->getUnsubscribeRequester($systemInstall);

        foreach ($this->webhookRepository->getWebhooksForUnsubscribe($systemInstall) as $webhook) {
            try {
                $request     = $requester->getRequestDto([
                    RequesterInterface::WEBHOOK_ID => $webhook->getWebhookId() ?? '',
                ]);
                $responseDto = $this->curl->send($request);
                if ($requester->processResponse($responseDto, $systemInstall)) {
                    $this->dm->remove($webhook);
                } else {
                    $this->unsubscribeFailed($webhook, NULL, $responseDto);
                }
            } catch (Exception $e) {
                $this->unsubscribeFailed($webhook, $e, NULL);
            }
        }
        $this->dm->flush();
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     */
    public function update(WebhookSystemInterface $system, string $userId, string $token): void
    {
        $this->unsubscribe($system, $userId);
        $this->subscribe($system, $userId, $token);
    }

    /**
     * ------------------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param Webhook          $webhook
     * @param Exception        $e
     * @param ResponseDto|null $res
     */
    private function unsubscribeFailed(Webhook $webhook, ?Exception $e = NULL, ?ResponseDto $res = NULL): void
    {
        $text = $res ? $res->getBody() : NULL;
        $this->logger->error(
            sprintf('Webhook [%s] failed to unsubscribe.', $webhook->getId()),
            ['exception' => $e, 'response' => $text]
        );
        $webhook->setUnsubscribeFailed(TRUE);
    }

    /**
     * @param string $systemKey
     * @param string $userId
     *
     * @return SystemInstall
     * @throws SystemException
     */
    private function getSystemInstall(string $systemKey, string $userId): SystemInstall
    {
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemRepository->findOneBy([
            'system' => $systemKey,
            'user'   => $userId,
        ]);

        if (!$systemInstall) {
            throw new SystemException(
                sprintf('System ["%s"] not found for user ["%s"]', $systemKey, $userId)
            );
        }

        return $systemInstall;
    }

    /**
     * @param SystemInstall     $systemInstall
     * @param WebhookSubscribes $subscribes
     *
     * @return string
     */
    private function getWebhookUrl(SystemInstall $systemInstall, WebhookSubscribes $subscribes): string
    {
        return WebhookUtils::getWebhookUrl(
            $this->domain,
            $systemInstall,
            $subscribes->getNodeName(),
            $subscribes->getTopologyName()
        );
    }

    /**
     * @param string            $userId
     * @param WebhookSubscribes $sub
     * @param SystemInstall     $system
     *
     * @return bool
     */
    private function isSkippable(string $userId, WebhookSubscribes $sub, SystemInstall $system): bool
    {
        return $this->webhookRepository->isWebhookRegistred(
            $userId,
            $system->getSystem(),
            $sub->getTopologyName(),
            $sub->getNodeName()
        );
    }

    /**
     * @param string            $user
     * @param WebhookSubscribes $sub
     * @param string            $key
     * @param string|null       $id
     */
    private function createWebhook(string $user, WebhookSubscribes $sub, string $key, ?string $id = NULL): void
    {
        $webhook = new Webhook();
        $webhook->setUser($user)
            ->setNodeName($sub->getNodeName())
            ->setSystemKey($key)
            ->setTopologyName($sub->getTopologyName())
            ->setWebhookId($id);
        $this->dm->persist($webhook);
    }

}