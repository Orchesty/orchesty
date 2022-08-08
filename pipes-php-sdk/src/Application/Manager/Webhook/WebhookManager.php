<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Manager\Webhook;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Application\Repository\WebhookRepository;

/**
 * Class WebhookManager
 *
 * @package Hanaboso\PipesPhpSdk\Application\Manager\Webhook
 */
final class WebhookManager
{

    private const URL    = '%s/webhook/topologies/%s/nodes/%s/token/%s';
    private const LENGTH = 25;

    /**
     * @var string
     */
    private string $hostname;

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private $repository;

    /**
     * @var ObjectRepository<Webhook>&WebhookRepository
     */
    private $webhookRepository;

    /**
     * WebhookManager constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $manager
     * @param string               $hostname
     */
    public function __construct(private DocumentManager $dm, private CurlManagerInterface $manager, string $hostname)
    {
        $this->hostname          = rtrim($hostname, '/');
        $this->repository        = $dm->getRepository(ApplicationInstall::class);
        $this->webhookRepository = $dm->getRepository(Webhook::class);
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     *
     * @return mixed[]
     */
    public function getWebhooks(WebhookApplicationInterface $application, string $userId): array
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->webhookRepository->findBy(
            [
                'application' => $application->getName(),
                'user'        => $userId,
            ],
        );

        return array_map(
            static function (WebhookSubscription $subscription) use ($webhooks): array {
                $topology = $subscription->getTopology();
                $enabled  = FALSE;

                $webhooks = array_filter(
                    $webhooks,
                    static fn(Webhook $webhook): bool => $webhook->getName() === $subscription->getName(),
                );

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
            },
            $application->getWebhookSubscriptions(),
        );
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     * @param mixed[]                     $data
     *
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws Exception
     */
    public function subscribeWebhooks(WebhookApplicationInterface $application, string $userId, array $data = []): void
    {
        foreach ($application->getWebhookSubscriptions() as $subscription) {
            if (!$data && !$subscription->getTopology() ||
                $data && $data[WebhookSubscription::NAME] !== $subscription->getName()
            ) {
                continue;
            }

            $name               = $data[WebhookSubscription::TOPOLOGY] ?? $subscription->getTopology();
            $token              = bin2hex(random_bytes(self::LENGTH));
            $applicationInstall = $this->repository->findUserApp($application->getName(), $userId);
            $request            = $application->getWebhookSubscribeRequestDto(
                $applicationInstall,
                $subscription,
                sprintf(self::URL, $this->hostname, $name, $subscription->getNode(), $token),
            );

            $webhookId = $application->processWebhookSubscribeResponse(
                $this->manager->send($request),
                $applicationInstall,
            );

            $webhook = (new Webhook())
                ->setName($subscription->getName())
                ->setUser($userId)
                ->setNode($subscription->getNode())
                ->setTopology($name)
                ->setApplication($application->getName())
                ->setWebhookId($webhookId)
                ->setToken($token);
            $this->dm->persist($webhook);
        }

        $this->dm->flush();
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     * @param mixed[]                     $data
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws MongoDBException
     */
    public function unsubscribeWebhooks(
        WebhookApplicationInterface $application,
        string $userId,
        array $data = [],
    ): void
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->webhookRepository->findBy(
            [
                Webhook::APPLICATION => $application->getName(),
                Webhook::USER        => $userId,
            ],
        );

        foreach ($webhooks as $webhook) {
            if ($data && $data[WebhookSubscription::TOPOLOGY] !== $webhook->getTopology()) {
                continue;
            }

            $request = $application->getWebhookUnsubscribeRequestDto(
                $this->repository->findUserApp($application->getName(), $userId),
                $webhook->getWebhookId(),
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
