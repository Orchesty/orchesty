<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Application\Model\CustomAction\CustomAction;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use InvalidArgumentException;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler
 */
final class ApplicationHandler
{

    private const SYNC_METHODS         = 'syncMethods';
    private const AUTHORIZED           = 'authorized';
    private const ENABLED              = 'enabled';
    private const WEBHOOK_SETTINGS     = 'webhookSettings';
    private const APPLICATION_SETTINGS = 'applicationSettings';
    private const CUSTOM_ACTIONS       = 'customActions';

    /**
     * ApplicationHandler constructor.
     *
     * @param ApplicationManager $applicationManager
     * @param WebhookManager     $webhookManager
     */
    public function __construct(private ApplicationManager $applicationManager, private WebhookManager $webhookManager)
    {
    }

    /**
     * @return mixed[]
     * @throws ApplicationInstallException
     */
    public function getApplications(): array
    {
        return [
            'items' => array_map(
                fn(string $key): array => $this->applicationManager->getApplication($key)->toArray(),
                $this->applicationManager->getApplications(),
            ),
        ];
    }

    /**
     * @param string   $user
     * @param string[] $applications
     *
     * @return string[]
     */
    public function getApplicationsLimits(string $user, array $applications): array
    {
        return $this->applicationManager->getApplicationsLimits($user, $applications);
    }

    /**
     * @param string $key
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws ReflectionException
     */
    public function getApplicationByKey(string $key): array
    {
        return array_merge(
            $this->applicationManager->getApplication($key)->toArray(),
            [
                self::SYNC_METHODS => $this->applicationManager->getSynchronousActions($key),
            ],
        );
    }

    /**
     * @param string $key
     *
     * @return string[]
     * @throws ApplicationInstallException
     * @throws ReflectionException
     */
    public function getSynchronousActions(string $key): array
    {
        return $this->applicationManager->getSynchronousActions($key);
    }

    /**
     * @param string  $key
     * @param string  $method
     * @param Request $request
     *
     * @return mixed
     * @throws ApplicationInstallException
     */
    public function runSynchronousAction(string $key, string $method, Request $request): mixed
    {
        return $this->applicationManager->runSynchronousAction($key, $method, $request);
    }

    /**
     * @param string $user
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     */
    public function getApplicationsByUser(string $user): array
    {
        return [
            'items' => array_map(
                function (ApplicationInstall $applicationInstall): array {
                    $key         = $applicationInstall->getKey();
                    $application = $this->applicationManager->getApplication($key);

                    return array_merge(
                        $applicationInstall->toArray(),
                        [
                            self::AUTHORIZED => $application->isAuthorized($applicationInstall),
                        ],
                    );
                },
                $this->applicationManager->getInstalledApplications($user),
            ),
        ];
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     * @throws ApplicationInstallException
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
                self::ENABLED              => $applicationInstall->isEnabled(),
                self::APPLICATION_SETTINGS => $application->getApplicationForms($applicationInstall),
                self::WEBHOOK_SETTINGS     => $application->getApplicationType() === ApplicationTypeEnum::WEBHOOK ?
                    $this->webhookManager->getWebhooks($application, $user) :
                    [],
                self::CUSTOM_ACTIONS       => $this->customActionsToArray($application->getCustomActions()),
            ],
        );
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws MongoDBException
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
                self::APPLICATION_SETTINGS => $application->getApplicationForms($applicationInstall),
            ],
        );
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws MongoDBException
     * @throws CurlException
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
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws Exception
     */
    public function updateApplicationSettings(string $key, string $user, array $data): array
    {
        return $this->applicationManager->saveApplicationSettings($key, $user, $data);
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function updateApplicationPassword(string $key, string $user, array $data): array
    {
        if (!array_key_exists(BasicApplicationInterface::PASSWORD, $data)) {
            throw new InvalidArgumentException('Field password is not included.');
        }

        if (!array_key_exists('formKey', $data)) {
            throw new InvalidArgumentException('Field formKey is not included.');
        }

        if (!array_key_exists('fieldKey', $data)) {
            throw new InvalidArgumentException('Field fieldKey is not included.');
        }

        return $this->applicationManager->saveApplicationPassword(
            $key,
            $user,
            $data['formKey'],
            $data['fieldKey'],
            $data[BasicApplicationInterface::PASSWORD],
        )->toArray();
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $redirectUrl
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function authorizeApplication(string $key, string $user, string $redirectUrl): string
    {
        return $this->applicationManager->authorizeApplication($key, $user, $redirectUrl);
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $token
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function saveAuthToken(string $key, string $user, array $token): string
    {
        return $this->applicationManager->saveAuthorizationToken($key, $user, $token);
    }

    /**
     * @param string $key
     * @param string $user
     * @param bool   $enabled
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function changeStateOfApplication(string $key, string $user, bool $enabled): ApplicationInstall
    {
        return $this->applicationManager->changeStateOfApplication($key, $user, $enabled);
    }

    /**
     * @param CustomAction[] $customActions
     *
     * @return mixed[]
     */
    private function customActionsToArray(array $customActions): array
    {
        $arr = [];
        foreach ($customActions as $action) {
            $arr[] = $action->toArray();
        }

        return $arr;
    }

}
