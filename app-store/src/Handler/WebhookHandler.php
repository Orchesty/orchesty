<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Handler;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFAppStore\Model\ApplicationManager;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;

/**
 * Class WebhookHandler
 *
 * @package Hanaboso\HbPFAppStore\Handler
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
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @throws ApplicationInstallException
     * @throws PipesFrameworkException
     * @throws Exception
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
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
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
