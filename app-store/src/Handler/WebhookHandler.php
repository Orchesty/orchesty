<?php declare(strict_types=1);

namespace Hanaboso\HbPFApplication\Handler;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\HbPFApplication\Model\ApplicationManager;
use Hanaboso\HbPFApplication\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;

/**
 * Class WebhookHandler
 *
 * @package Hanaboso\HbPFApplication\Handler
 */
class WebhookHandler
{

    /**
     * @var ApplicationManager
     */
    private $applicationManager;

    /**
     * WebhookHandler constructor.
     *
     * @param ApplicationManager $applicationManager
     */
    public function __construct(ApplicationManager $applicationManager)
    {
        $this->applicationManager = $applicationManager;
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $data
     *
     * @throws ApplicationInstallException
     * @throws PipesFrameworkException
     */
    public function subscribeWebhooks(string $key, string $user, array $data = []): void
    {
        if ($data) {
            ControllerUtils::checkParameters([WebhookSubscription::NAME, WebhookSubscription::TOPOLOGY], $data);
        }

        $this->applicationManager->subscribeWebhooks(
            $this->applicationManager->getInstalledApplicationDetail($key, $user),
            $data
        );
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $data
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws PipesFrameworkException
     */
    public function unsubscribeWebhooks(string $key, string $user, array $data = []): void
    {
        if ($data) {
            ControllerUtils::checkParameters([WebhookSubscription::NAME, WebhookSubscription::TOPOLOGY], $data);
        }

        $this->applicationManager->unsubscribeWebhooks(
            $this->applicationManager->getInstalledApplicationDetail($key, $user),
            $data
        );
    }

}
