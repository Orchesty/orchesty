<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Handler;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\HbPFAppStore\Model\ApplicationManager;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use InvalidArgumentException;

/**
 * Class ApplicationHandler
 *
 * @package Hanaboso\HbPFAppStore\Handler
 */
class ApplicationHandler
{

    private const AUTHORIZED           = 'authorized';
    private const WEBHOOK_SETTINGS     = 'webhookSettings';
    private const APPLICATION_SETTINGS = 'applicationSettings';

    /**
     * @var ApplicationManager
     */
    private $applicationManager;

    /**
     * @var WebhookManager
     */
    private $webhookManager;

    /**
     * ApplicationHandler constructor.
     *
     * @param ApplicationManager $applicationManager
     * @param WebhookManager     $webhookManager
     */
    public function __construct(ApplicationManager $applicationManager, WebhookManager $webhookManager)
    {
        $this->applicationManager = $applicationManager;
        $this->webhookManager     = $webhookManager;
    }

    /**
     * @return array
     */
    public function getApplications(): array
    {
        return [
            'items' => array_map(function (string $key): array {
                return $this->applicationManager->getApplication($key)->toArray();
            }, $this->applicationManager->getApplications()),
        ];
    }

    /**
     * @param string $key
     *
     * @return array
     * @throws Exception
     */
    public function getApplicationByKey(string $key): array
    {
        return $this->applicationManager->getApplication($key)->toArray();
    }

    /**
     * @param string $user
     *
     * @return array
     */
    public function getApplicationsByUser(string $user): array
    {
        /** @var BasicApplicationAbstract&WebhookApplicationInterface $application */
        return [
            'items' => array_map(function (ApplicationInstall $applicationInstall): array {
                $application = $this->applicationManager->getApplication($applicationInstall->getKey());

                return array_merge(
                    $applicationInstall->toArray(),
                    [self::AUTHORIZED => $application->isAuthorized($applicationInstall)]
                );
            }, $this->applicationManager->getInstalledApplications($user)),
        ];
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return array
     * @throws ApplicationInstallException
     * @throws Exception
     */
    public function getApplicationByKeyAndUser(string $key, string $user): array
    {
        /** @var BasicApplicationAbstract&WebhookApplicationInterface $application */
        $application        = $this->applicationManager->getApplication($key);
        $applicationInstall = $this->applicationManager->getInstalledApplicationDetail($key, $user);

        return array_merge(
            $application->toArray(),
            [
                self::AUTHORIZED           => $application->isAuthorized($applicationInstall),
                self::APPLICATION_SETTINGS => $application->getApplicationForm($applicationInstall),
                self::WEBHOOK_SETTINGS     => $application->getApplicationType() === ApplicationTypeEnum::WEBHOOK ?
                    $this->webhookManager->getWebhooks($application, $user) :
                    [],
            ],
        );
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return array
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function installApplication(string $key, string $user): array
    {
        /** @var BasicApplicationAbstract $application */
        $application        = $this->applicationManager->getApplication($key);
        $applicationInstall = $this->applicationManager->installApplication($key, $user);

        return array_merge(
            $application->toArray(),
            [
                self::AUTHORIZED           => $application->isAuthorized($applicationInstall),
                self::APPLICATION_SETTINGS => $application->getApplicationForm($applicationInstall),
            ],
        );
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return array
     * @throws Exception
     */
    public function uninstallApplication(string $key, string $user): array
    {
        return array_merge(
            $this->applicationManager->uninstallApplication($key, $user)->toArray(),
            [
                self::AUTHORIZED           => FALSE,
                self::APPLICATION_SETTINGS => NULL,
            ],
        );
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $data
     *
     * @return array
     * @throws Exception
     */
    public function updateApplicationSettings(string $key, string $user, array $data): array
    {
        return array_merge(
            $this->applicationManager->saveApplicationSettings($key, $user, $data)->toArray(),
            [self::APPLICATION_SETTINGS => $this->applicationManager->getApplicationSettings($key, $user)],
        );
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $data
     *
     * @return array
     * @throws Exception
     */
    public function updateApplicationPassword(string $key, string $user, array $data): array
    {
        if (!array_key_exists('password', $data)) {
            throw new InvalidArgumentException('Field password is not included.');
        }
        $password = $data['password'];

        return $this->applicationManager->saveApplicationPassword($key, $user, $password)->toArray();
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $redirectUrl
     *
     * @throws Exception
     */
    public function authorizeApplication(string $key, string $user, string $redirectUrl): void
    {
        $this->applicationManager->authorizeApplication($key, $user, $redirectUrl);
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $token
     *
     * @return array
     * @throws ApplicationInstallException
     */
    public function saveAuthToken(string $key, string $user, array $token): array
    {
        return $this->applicationManager->saveAuthorizationToken($key, $user, $token);
    }

}
