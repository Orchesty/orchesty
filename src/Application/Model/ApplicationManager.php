<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\PipesFramework\Application\Base\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Base\OAuth1ApplicationInterface;
use Hanaboso\PipesFramework\Application\Base\OAuth2ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Loader\ApplicationLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * @var EntityRepository
     */
    private $repository;

    /**
     * ApplicationManager constructor.
     *
     * @param DocumentManager    $dm
     * @param ContainerInterface $container
     */
    public function __construct(DocumentManager $dm, ContainerInterface $container)
    {
        $this->loader     = new ApplicationLoader($container);
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
        return $this->repository->findBy(['user' => $user]);
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
     * @throws DateTimeException
     * @throws ApplicationInstallException
     */
    public function installApplication(string $key, string $user): ApplicationInstall
    {
        if ($this->repository->findOneBy(['user' => $user, 'key' => $key])) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was already installed.', $key),
                ApplicationInstallException::APP_ALREADY_INSTALLED
            );
        }

        $app = new ApplicationInstall();
        $app
            ->setUser($user)
            ->setKey($key);
        $this->dm->persist($app);
        $this->dm->flush($app);

        return $app;
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
        $app = $this->repository->findUserApp($key, $user);
        $this->dm->remove($app);
        $this->dm->flush($app);

        return $app;
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
        $app = $this->repository->findUserApp($key, $user);

        $updatedApp = $this->loader->getApplication($key)->setApplicationSettings($app, $data);
        $this->dm->persist($updatedApp);
        $this->dm->flush($updatedApp);

        return $updatedApp;
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
        $app = $this->repository->findUserApp($key, $user);

        /** @var ApplicationInstall $updatedApp */
        $updatedApp = $this->loader->getApplication($key)->setApplicationPassword($app, $password);
        $this->dm->persist($updatedApp);
        $this->dm->flush($updatedApp);

        return $updatedApp;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws Exception
     */
    public function authorizeApplication(string $key, string $user): void
    {
        /** @var ApplicationInstall $app */
        $app = $this->repository->findUserApp($key, $user);

        /** @var OAuth1ApplicationInterface|OAuth2ApplicationInterface $appAuth */
        $appAuth = $this->loader->getApplication($key);
        $appAuth->authorize($app);
    }

}
