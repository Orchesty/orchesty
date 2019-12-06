<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use InvalidArgumentException;

/**
 * Class ApplicationHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler
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
     * @return mixed[]
     */
    public function getApplications(): array
    {
        return $this->applicationManager->getApplications();
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
        return $this->applicationManager->saveApplicationSettings($key, $user, $data)->toArray();
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @return mixed[]
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
     * @param string  $key
     * @param string  $user
     * @param mixed[] $token
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function saveAuthToken(string $key, string $user, array $token): array
    {
        return $this->applicationManager->saveAuthorizationToken($key, $user, $token);
    }

}
