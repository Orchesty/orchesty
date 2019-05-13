<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Model;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\PipesFramework\Application\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesFramework\Application\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Loader\ApplicationLoader;

/**
 * Class ApplicationManager
 *
 * @package Hanaboso\PipesFramework\Application\Model
 */
class ApplicationManager
{

    /**
     * @var ApplicationLoader
     */
    private $loader;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ObjectRepository|ApplicationInstallRepository
     */
    private $repository;

    /**
     * ApplicationManager constructor.
     *
     * @param DocumentManager   $dm
     * @param ApplicationLoader $loader
     */
    public function __construct(DocumentManager $dm, ApplicationLoader $loader)
    {
        $this->loader     = $loader;
        $this->dm         = $dm;
        $this->repository = $this->dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return array
     */
    public function getApplications(): array
    {
        return $this->loader->getApplications();
    }

    /**
     * @param string $key
     *
     * @return BasicApplicationInterface
     * @throws Exception
     */
    public function getApplication(string $key): BasicApplicationInterface
    {
        return $this->loader->getApplication($key);
    }

    /**
     * @param string $user
     *
     * @return array
     */
    public function getInstalledApplications(string $user): array
    {
        return $this->repository->findBy([ApplicationInstall::USER => $user]);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function getInstalledApplicationDetail(string $key, string $user): ApplicationInstall
    {
        return $this->repository->findUserApp($key, $user);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws DateTimeException
     */
    public function installApplication(string $key, string $user): ApplicationInstall
    {
        if ($this->repository->findOneBy([ApplicationInstall::USER => $user, ApplicationInstall::KEY => $key])) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was already installed.', $key),
                ApplicationInstallException::APP_ALREADY_INSTALLED
            );
        }

        $applicationInstall = new ApplicationInstall();
        $applicationInstall
            ->setUser($user)
            ->setKey($key);
        $this->dm->persist($applicationInstall);
        $this->dm->flush($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public function uninstallApplication(string $key, string $user): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);
        $this->dm->remove($applicationInstall);
        $this->dm->flush($applicationInstall);

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $data
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public function saveApplicationSettings(string $key, string $user, array $data): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);

        $application = $this->loader->getApplication($key)->setApplicationSettings($applicationInstall, $data);
        $this->dm->persist($application);
        $this->dm->flush($application);

        return $application;
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $password
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public function saveApplicationPassword(string $key, string $user, string $password): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);

        /** @var ApplicationInstall $application */
        $application = $this->loader->getApplication($key)->setApplicationPassword($applicationInstall, $password);
        $this->dm->persist($application);
        $this->dm->flush($application);

        return $application;
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $redirectUrl
     *
     * @throws ApplicationInstallException
     */
    public function authorizeApplication(string $key, string $user, string $redirectUrl): void
    {
        /** @var ApplicationInstall $applicationInstall */
        $applicationInstall = $this->repository->findUserApp($key, $user);
        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setAuthorizationRedirectUrl($applicationInstall, $redirectUrl);

        $this->dm->flush();

        $application->authorize($applicationInstall);
    }

    /**
     * @param string $key
     * @param string $user
     * @param array  $token
     *
     * @return array
     * @throws ApplicationInstallException
     */
    public function setApplicationAuthorizationToken(string $key, string $user, array $token): array
    {
        /** @var ApplicationInstall $applicationInstall */
        $applicationInstall = $this->repository->findUserApp($key, $user);
        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $application */
        $application = $this->loader->getApplication($key);
        $application->setAuthorizationToken($applicationInstall, $token);
        $this->dm->flush();

        return [BasicApplicationInterface::REDIRECT_URL => $application->getAuthorizationRedirectUrl($applicationInstall)];
    }

}
