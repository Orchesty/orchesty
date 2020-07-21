<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler
 */
final class ApplicationHandler
{

    /**
     * @var ApplicationManager
     */
    private ApplicationManager $applicationManager;

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
        return [
            'items' => array_map(
                fn(string $key): array => $this->applicationManager->getApplication($key)->toArray(),
                $this->applicationManager->getApplications()
            ),
        ];
    }

    /**
     * @param string $key
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     */
    public function getApplicationByKey(string $key): array
    {
        return $this->applicationManager->getApplication($key)->toArray();
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
    public function runSynchronousAction(string $key, string $method, Request $request)
    {
        return $this->applicationManager->runSynchronousAction($key, $method, $request);
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
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws MongoDBException
     */
    public function saveAuthToken(string $key, string $user, array $token): array
    {
        return $this->applicationManager->saveAuthorizationToken($key, $user, $token);
    }

}
