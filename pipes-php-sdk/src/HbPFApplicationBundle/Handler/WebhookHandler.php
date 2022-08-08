<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;

/**
 * Class WebhookHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler
 */
final class WebhookHandler
{

    /**
     * WebhookHandler constructor.
     *
     * @param ApplicationManager $applicationManager
     */
    public function __construct(private ApplicationManager $applicationManager)
    {
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @throws ApplicationInstallException
     * @throws PipesFrameworkException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function subscribeWebhooks(string $key, string $user, array $data = []): void
    {
        if ($data) {
            ControllerUtils::checkParameters([WebhookSubscription::NAME, WebhookSubscription::TOPOLOGY], $data);
        }

        $this->applicationManager->subscribeWebhooks(
            $this->applicationManager->getInstalledApplicationDetail($key, $user),
            $data,
        );
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function unsubscribeWebhooks(string $key, string $user, array $data = []): void
    {
        if ($data) {
            ControllerUtils::checkParameters([WebhookSubscription::NAME, WebhookSubscription::TOPOLOGY], $data);
        }

        $this->applicationManager->unsubscribeWebhooks(
            $this->applicationManager->getInstalledApplicationDetail($key, $user),
            $data,
        );
    }

}
