<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Webhook;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Exception;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class WebhookManager
 *
 * @package CleverConnectors\AppBundle\Model\Webhook
 */
class WebhookManager implements LoggerAwareInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var WebhookRepository|DocumentRepository
     */
    private $webhookRepository;

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
     * WebhookManager constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     * @param string               $domain
     */
    function __construct(DocumentManager $dm, CurlManagerInterface $curl, string $domain)
    {
        $this->dm                = $dm;
        $this->webhookRepository = $dm->getRepository(Webhook::class);
        $this->curl              = $curl;
        $this->logger            = new NullLogger();
        $this->domain            = $domain;
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     * @param bool                   $isUpdate
     *
     * @return array
     */
    public function subscribe(WebhookSystemInterface $system, string $userId, string $token, $isUpdate = FALSE): array
    {
        $systemInstall = $this->dm->getRepository(SystemInstall::class)->findOneBy([
            'system' => $system->getKey(), 'user' => $userId,
        ]);

        $ids = [];
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

            $url = $this->getWebhookUrl($this->domain, $userId, $token, $sub->getNodeName(), $sub->getTopologyName());

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
            $ids[] = $doc->getId();
        }
        $this->dm->flush();

        return $ids;
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param array|null             $new
     */
    public function unsubscribe(WebhookSystemInterface $system, string $userId, array $new = []): void
    {
        $systemInstall = $this->dm->getRepository(SystemInstall::class)->findOneBy([
            'system' => $system->getKey(), 'user' => $userId,
        ]);

        $arr = $this->dm->getRepository(Webhook::class)->findBy([
            'user'      => $userId,
            'systemKey' => $system->getKey(),
        ]);

        foreach ($arr as $sub) {
            if (in_array($sub->getId(), $new)) {
                continue;
            }

            $req = $system->getUnsubscribeRequest($systemInstall, $sub->getWebhookId());
            try {
                $res = $this->curl->send($req);
                if ($res->getStatusCode() == 200) {
                    $this->dm->remove($sub);
                } else {
                    $this->logger->error(
                        sprintf('Webhook [%s] failed to unsubscribe.', $sub->getId()),
                        ['exception' => $res->getBody()]
                    );
                    $sub->setUnsubscribeFailed(TRUE);
                }
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf('Webhook [%s] failed to unsubscribe.', $sub->getId()),
                    ['exception' => $e]
                );
                $sub->setUnsubscribeFailed(TRUE);
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
        // Shopify forbides subscription on same entity/action and address -> first needs unsubscribe
        $this->unsubscribe($system, $userId);
        $this->subscribe($system, $userId, $token);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return WebhookManager
     */
    public function setLogger(LoggerInterface $logger): WebhookManager
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $domain
     * @param string $userId
     * @param string $token
     * @param string $nodeName
     * @param string $topologyName
     *
     * @return string
     */
    private function getWebhookUrl(string $domain, string $userId, string $token, string $nodeName,
                                   string $topologyName): string
    {
        return sprintf('%s/webhook/%s/%s/%s/%s', $domain, $userId, $token, $nodeName, $topologyName);
    }

}