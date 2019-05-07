<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Model\Webhook;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Document\Webhook;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;

/**
 * Class WebhookManager
 *
 * @package Hanaboso\PipesFramework\Application\Model\Webhook
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
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     */
    public function subscribeWebhooks(WebhookApplicationInterface $application, string $userId): void
    {
        foreach ($application->getWebhookSubscriptions() as $subscription) {
            $token     = bin2hex(random_bytes(self::LENGTH));
            $request   = $application->getWebhookSubscribeRequestDto(
                $subscription,
                sprintf(self::URL, $this->hostname, $subscription->getTopology(), $subscription->getNode(), $token)
            );
            $webhookId = $application->processWebhookSubscribeResponse(
                $this->manager->send($request),
                $this->getApplicationInstall($application, $userId)
            );

            $webhook = (new Webhook())
                ->setUser($userId)
                ->setNode($subscription->getNode())
                ->setTopology($subscription->getTopology())
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
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function unsubscribeWebhooks(WebhookApplicationInterface $application, string $userId): void
    {
        /** @var Webhook[] $webhooks */
        $webhooks = $this->dm->getRepository(Webhook::class)->findBy([
            Webhook::APPLICATION => $application->getKey(),
            Webhook::USER        => $userId,
        ]);

        foreach ($webhooks as $webhook) {
            $request = $application->getWebhookUnsubscribeRequestDto($webhook->getWebhookId());
            if ($application->processWebhookUnsubscribeResponse($this->manager->send($request))) {
                $this->dm->remove($webhook);
            } else {
                $webhook->setUnsubscribeFailed(TRUE);
            }
        }

        $this->dm->flush();
    }

    /**
     * @param WebhookApplicationInterface $application
     * @param string                      $userId
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    private function getApplicationInstall(WebhookApplicationInterface $application, string $userId): ApplicationInstall
    {
        // TODO: refactor

        /** @var ApplicationInstall|NULL $install */
        $install = $this->repository->findOneBy([
            'user' => $userId,
            'key'  => $application->getKey(),
        ]);

        if (!$install) {
            throw new ApplicationInstallException(
                sprintf(
                    'ApplicationInstall for given application [%s] and user [%s] not found!',
                    $application->getKey(),
                    $userId
                ),
                ApplicationInstallException::APPLICATION_INSTALL_NOT_FOUND
            );
        }

        return $install;
    }

}
