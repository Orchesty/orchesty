<?php declare(strict_types=1);

namespace Hanaboso\HbPFApplication\Model\Webhook;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\HbPFApplication\Document\Webhook;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;

/**
 * Class WebhookManager
 *
 * @package Hanaboso\HbPFApplication\Model\Webhook
 */
final class WebhookManager
{

    private const URL    = '%s/webhook/topologies/%s/nodes/%s/token/%s';
    private const LENGTH = 25;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CurlManagerInterface
     */
    private $manager;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     * WebhookManager constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $manager
     * @param string               $hostname
     */
    public function __construct(DocumentManager $dm, CurlManagerInterface $manager, string $hostname)
    {
        $this->dm         = $dm;
        $this->manager    = $manager;
        $this->hostname   = rtrim($hostname, '/');
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     *
     * @return array
     */
    public function getWebhooks(WebhookApplicationInterface $application, string $userId): array
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->dm->getRepository(Webhook::class)->findBy([
            'application' => $application->getKey(),
            'user'        => $userId,
        ]);

        return array_map(function (WebhookSubscription $subscription) use ($webhooks): array {
            $topology = $subscription->getTopology();
            $enabled  = FALSE;

            $webhooks = array_filter($webhooks, function (Webhook $webhook) use ($subscription): bool {
                return $webhook->getName() === $subscription->getName();
            });

            if ($webhooks) {
                $topology = array_values($webhooks)[0]->getTopology();
                $enabled  = TRUE;
            }

            return [
                'name'     => $subscription->getName(),
                'default'  => $subscription->getTopology() !== '',
                'enabled'  => $enabled,
                'topology' => $topology,
            ];
        }, $application->getWebhookSubscriptions());
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     * @param array                       $data
     *
     * @throws Exception
     */
    public function subscribeWebhooks(WebhookApplicationInterface $application, string $userId, array $data = []): void
    {
        foreach ($application->getWebhookSubscriptions() as $subscription) {
            if (!$data && !$subscription->getTopology() || $data && $data[WebhookSubscription::NAME] !== $subscription->getName()) {
                continue;
            }

            $name               = $data[WebhookSubscription::TOPOLOGY] ?? $subscription->getTopology();
            $token              = bin2hex(random_bytes(self::LENGTH));
            $applicationInstall = $this->repository->findUserApp($application->getKey(), $userId);
            $request            = $application->getWebhookSubscribeRequestDto(
                $applicationInstall,
                $subscription,
                sprintf(self::URL, $this->hostname, $name, $subscription->getNode(), $token)
            );
            $webhookId          = $application->processWebhookSubscribeResponse(
                $this->manager->send($request),
                $applicationInstall
            );

            $webhook = (new Webhook())
                ->setName($subscription->getName())
                ->setUser($userId)
                ->setNode($subscription->getNode())
                ->setTopology($name)
                ->setApplication($application->getKey())
                ->setWebhookId($webhookId)
                ->setToken($token);
            $this->dm->persist($webhook);
        }

        $this->dm->flush();
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     * @param array                       $data
     *
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function unsubscribeWebhooks(
        WebhookApplicationInterface $application,
        string $userId,
        array $data = []
    ): void {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->dm->getRepository(Webhook::class)->findBy([
            Webhook::APPLICATION => $application->getKey(),
            Webhook::USER        => $userId,
        ]);

        foreach ($webhooks as $webhook) {
            if ($data && $data[WebhookSubscription::TOPOLOGY] !== $webhook->getTopology()) {
                continue;
            }

            $request = $application->getWebhookUnsubscribeRequestDto(
                $this->repository->findUserApp($application->getKey(), $userId),
                $webhook->getWebhookId()
            );
            if ($application->processWebhookUnsubscribeResponse($this->manager->send($request))) {
                $this->dm->remove($webhook);
            } else {
                $webhook->setUnsubscribeFailed(TRUE);
            }
        }

        $this->dm->flush();
    }

}
