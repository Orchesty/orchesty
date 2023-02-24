<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Manager\Webhook;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Application\Repository\WebhookFilter;
use Hanaboso\PipesPhpSdk\Application\Repository\WebhookRepository;
use Hanaboso\Utils\Exception\DateTimeException;

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
     * WebhookManager constructor.
     *
     * @param ApplicationInstallRepository $applicationInstallRepository
     * @param WebhookRepository            $webhookRepository
     * @param CurlManagerInterface         $manager
     * @param string                       $hostname
     */
    public function __construct(
        private readonly ApplicationInstallRepository $applicationInstallRepository,
        private readonly WebhookRepository $webhookRepository,
        private readonly CurlManagerInterface $manager,
        string $hostname,
    )
    {
        $this->hostname = rtrim($hostname, '/');
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     *
     * @return mixed[]
     * @throws GuzzleException
     */
    public function getWebhooks(WebhookApplicationInterface $application, string $userId): array
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->webhookRepository->findMany(
            new WebhookFilter(applications: [$application->getName()], userIds: [$userId]),
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
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws GuzzleException
     * @throws DateTimeException
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
            $applicationInstall = $this->applicationInstallRepository->findUserApp($application->getName(), $userId);
            $request            = $application->getWebhookSubscribeRequestDto(
                $applicationInstall,
                $subscription,
                sprintf(self::URL, $this->hostname, $name, $subscription->getNode(), $token),
            );

            $webhookId = $application->processWebhookSubscribeResponse(
                $this->manager->send($request),
                $applicationInstall,
            );

            $this->webhookRepository->insert(
                new Webhook(
                    [
                        'name'        => $subscription->getName(),
                        'user'        => $userId,
                        'node'        => $subscription->getName(),
                        'topology'    => $name,
                        'application' => $application->getName(),
                        'webhookId'   => $webhookId,
                        'token'       => $token,
                    ],
                ),
            );
        }
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     * @param mixed[]                     $data
     *
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws GuzzleException
     */
    public function unsubscribeWebhooks(
        WebhookApplicationInterface $application,
        string $userId,
        array $data = [],
    ): void
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->webhookRepository->findMany(
            new WebhookFilter(applications: [$application->getName()], userIds: [$userId]),
        );

        foreach ($webhooks as $webhook) {
            if ($data && $data[WebhookSubscription::TOPOLOGY] !== $webhook->getTopology()) {
                continue;
            }

            $request = $application->getWebhookUnsubscribeRequestDto(
                $this->applicationInstallRepository->findUserApp($application->getName(), $userId),
                $webhook,
            );
            if ($application->processWebhookUnsubscribeResponse($this->manager->send($request))) {
                $this->webhookRepository->remove($webhook);
            } else {
                $webhook->setUnsubscribeFailed(TRUE);
            }
        }
    }

}
