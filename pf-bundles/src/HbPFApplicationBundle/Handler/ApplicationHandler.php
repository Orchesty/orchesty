<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Handler;

use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesFramework\Application\Model\ApplicationManager;
use InvalidArgumentException;

/**
 * Class ApplicationHandler
 *
 * @package Hanaboso\PipesFramework\HbPFApplicationBundle\Handler
 */
class ApplicationHandler
{

    /**
     * @var ApplicationManager
     */
    private $applicationManager;

    /**
     * ApplicationHandler constructor.
     *
     * @param ApplicationManager $applicationManager
     */
    public function __construct(ApplicationManager $applicationManager)
    {
        $this->applicationManager = $applicationManager;
    }

    /**
     * @return array
     */
    public function getApplications(): array
    {
        return $this->applicationManager->getApplications();
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
        $applications = $this->applicationManager->getInstalledApplications($user);

        $res = [];
        /** @var ApplicationInstall $app */
        foreach ($applications as $app) {
            $res[] = $app->toArray();
        }

        return $res;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return array
     * @throws ApplicationInstallException
     */
    public function getApplicationByKeyAndUser(string $key, string $user): array
    {
        return $this->applicationManager->getInstalledApplicationDetail($key, $user)->toArray();
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return array
     * @throws ApplicationInstallException
     * @throws DateTimeException
     */
    public function installApplication(string $key, string $user): array
    {
        return $this->applicationManager->installApplication($key, $user)->toArray();
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
        return $this->applicationManager->uninstallApplication($key, $user)->toArray();
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
        return $this->applicationManager->saveApplicationSettings($key, $user, $data)->toArray();
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