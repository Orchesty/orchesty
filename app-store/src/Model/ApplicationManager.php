<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Model;

use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager as SdkApplicationManager;

/**
 * Class ApplicationManager
 *
 * @package Hanaboso\HbPFAppStore\Model
 */
final class ApplicationManager extends SdkApplicationManager
{

    /**
     * ApplicationManager constructor.
     *
     * @param DocumentManager   $dm
     * @param ApplicationLoader $loader
     * @param PsrCachedReader   $reader
     * @param WebhookManager    $webhook
     */
    public function __construct(
        DocumentManager $dm,
        ApplicationLoader $loader,
        PsrCachedReader $reader,
        private WebhookManager $webhook,
    )
    {
        parent::__construct($dm, $loader, $reader);
    }

    /**
     * @param string $user
     *
     * @return mixed[]
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
     * @throws MongoDBException
     */
    public function installApplication(string $key, string $user): ApplicationInstall
    {
        if ($this->repository->findOneBy([ApplicationInstall::USER => $user, ApplicationInstall::KEY => $key])) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was already installed.', $key),
                ApplicationInstallException::APP_ALREADY_INSTALLED,
            );
        }

        $applicationInstall = new ApplicationInstall();
        $applicationInstall
            ->setUser($user)
            ->setKey($key);
        $this->dm->persist($applicationInstall);
        $this->dm->flush();

        return $applicationInstall;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function uninstallApplication(string $key, string $user): ApplicationInstall
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);
        $this->unsubscribeWebhooks($applicationInstall);

        $this->dm->remove($applicationInstall);
        $this->dm->flush();

        return $applicationInstall;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $data
     *
     * @throws ApplicationInstallException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function subscribeWebhooks(ApplicationInstall $applicationInstall, array $data = []): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey());

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) &&
            $application->isAuthorized($applicationInstall)
        ) {
            $this->webhook->subscribeWebhooks($application, $applicationInstall->getUser(), $data);
        }
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $data
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws MongoDBException
     */
    public function unsubscribeWebhooks(ApplicationInstall $applicationInstall, array $data = []): void
    {
        /** @var WebhookApplicationInterface $application */
        $application = $this->loader->getApplication($applicationInstall->getKey());

        if (ApplicationTypeEnum::isWebhook($application->getApplicationType()) &&
            $application->isAuthorized($applicationInstall)
        ) {
            $this->webhook->unsubscribeWebhooks($application, $applicationInstall->getUser(), $data);
        }
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     */
    public function getApplicationSettings(string $key, string $user): array
    {
        $applicationInstall = $this->repository->findUserApp($key, $user);
        /** @var ApplicationAbstract $application */
        $application = $this->loader->getApplication($key);

        return $application->getApplicationForms($applicationInstall);
    }

}
