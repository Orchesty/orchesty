<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model;

use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Model\Systems\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Systems\WebhookSystemInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class WebhookManager
 *
 * @package CleverConnectors\AppBundle\Model
 */
class WebhookManager implements LoggerAwareInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * WebhookManager constructor.
     *
     * @param DocumentManager $dm
     * @param CurlManager     $curl
     */
    function __construct(DocumentManager $dm, CurlManager $curl)
    {
        $this->dm     = $dm;
        $this->curl   = $curl;
        $this->logger = new NullLogger();
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     * @param string                 $domain
     *
     * @return string[]
     */
    public function subscribe(WebhookSystemInterface $system, string $userId, string $token, string $domain): array
    {
        $ids = [];
        /** @var WebhookSubscribes $sub */
        foreach ($system->getWebhookSubscribes() as $sub) {
            $url = $this->getWebhookUrl($domain, $userId, $token, $sub->getNodeName(), $sub->getTopologyName());

            $req = $system->getSubscribeRequest($url);
            try {
                $res   = $this->curl->send($req);
                $id    = $system->getWebhookId($res);
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
        $arr = $this->dm->getRepository(Webhook::class)->findBy([
            'user'      => $userId,
            'systemKey' => $system->getKey(),
        ]);

        foreach ($arr as $sub) {
            if (in_array($sub->getId(), $new)) {
                continue;
            }

            $req = $system->getUnsubscribeRequest($sub->getWebhookId());
            try {
                $res = $this->curl->send($req);
                if ($res->getStatusCode() == 200) {
                    $this->dm->remove($sub);
                } else {
                    $this->logger->error(sprintf('Webhook [%s] failed to unsubscribe.', $sub->getId()), ['exception' => $res->getBody()]);
                    $sub->setUnsubscribeFailed(TRUE);
                }
            } catch (Exception $e) {
                $this->logger->error(sprintf('Webhook [%s] failed to unsubscribe.', $sub->getId()), ['exception' => $e]);
                $sub->setUnsubscribeFailed(TRUE);
            }
        }
        $this->dm->flush();
    }

    /**
     * @param WebhookSystemInterface $system
     * @param string                 $userId
     * @param string                 $token
     * @param string                 $domain
     */
    public function update(WebhookSystemInterface $system, string $userId, string $token, string $domain): void
    {
        $ids = $this->subscribe($system, $userId, $token, $domain);
        $this->unsubscribe($system, $userId, $ids);
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