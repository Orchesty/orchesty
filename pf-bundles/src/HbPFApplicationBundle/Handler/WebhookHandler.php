<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Handler;

use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Application\Model\ApplicationManager;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;

/**
 * Class WebhookHandler
 *
 * @package Hanaboso\PipesFramework\HbPFApplicationBundle\Handler
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
     *
     * @throws ApplicationInstallException
     */
    public function subscribeWebhooks(string $key, string $user): void
    {
        $application = $this->applicationManager->getInstalledApplicationDetail($key, $user);
        $this->applicationManager->subscribeWebhooks($application);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function unsubscribeWebhooks(string $key, string $user): void
    {
        $application = $this->applicationManager->getInstalledApplicationDetail($key, $user);
        $this->applicationManager->unsubscribeWebhooks($application);
    }

}
