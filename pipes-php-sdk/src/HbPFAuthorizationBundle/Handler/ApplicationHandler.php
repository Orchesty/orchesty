<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFAuthorizationBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Manager\ApplicationManager;
use InvalidArgumentException;

/**
 * Class ApplicationHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFAuthorizationBundle\Handler
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