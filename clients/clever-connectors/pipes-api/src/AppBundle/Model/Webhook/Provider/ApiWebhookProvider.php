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
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use CleverConnectors\AppBundle\Utils\WebhookUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
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

        /** @var WebhookSubscribes $sub */
        foreach ($system->getWebhookSubscribes() as $sub) {
            if (!$isUpdate && $this->webhookRepository->isWebhookRegistred(
                    $userId,
                    $system->getKey(),
                    $sub->getTopologyName(),
                    $sub->getNodeName()
                )
            ) {
                continue;
            }

            $url = WebhookUtils::getWebhookUrl(
                $this->domain,
                $userId,
                $token,
                $sub->getNodeName(),
                $sub->getTopologyName()
            );

            $req = $system->getSubscribeRequest($sub, $systemInstall, $url);
            try {
                $res = $this->curl->send($req);
                $id  = $system->getWebhookId($res);
            } catch (Exception $e) {
                $this->logger->error(sprintf('Webhook (nodeName, topologyName, system) [%s, %s, %s] failed to subscribe.',
                    $sub->getNodeName(), $sub->getTopologyName(), $system->getKey()), ['exception' => $e]);
                $id = NULL;
            }

            $doc = new Webhook();
            $doc->setUser($userId)
                ->setNodeName($sub->getNodeName())
                ->setSystemKey($system->getKey())
                ->setTopologyName($sub->getTopologyName())
                ->setWebhookId($id);
            $this->dm->persist($doc);
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

        /** @var Webhook[] $webhooks */
        $webhooks = $this->webhookRepository->findBy([
            'user'      => $userId,
            'systemKey' => $system->getKey(),
        ]);

        foreach ($webhooks as $webhook) {
            $req = $system->getUnsubscribeRequest($systemInstall, $webhook->getWebhookId() ?? '');
            try {
                $res = $this->curl->send($req);
                if (in_array($res->getStatusCode(), [200, 204])) {
                    $this->dm->remove($webhook);
                } else {
                    $this->unsubscribeFailed($webhook, NULL, $res);
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

}