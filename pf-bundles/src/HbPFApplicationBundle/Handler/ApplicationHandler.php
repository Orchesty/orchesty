<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Handler;

use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesFramework\Application\Model\ApplicationManager;

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
    public function getApplicationsByKey(string $key): array
    {
        $application = $this->applicationManager->getApplication($key);

        return [
            'name'        => $application->getName(),
            'type'        => $application->getType(),
            'key'         => $application->getKey(),
            'description' => $application->getDescription(),
        ];
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
     * @param string $password
     *
     * @return array
     * @throws Exception
     */
    public function updateApplicationPassword(string $key, string $user, string $password): array
    {
        return $this->applicationManager->saveApplicationPassword($key, $user, $password)->toArray();
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws Exception
     */
    public function authorizeApplication(string $key, string $user): void
    {
        $this->applicationManager->authorizeApplication($key, $user);
    }

}