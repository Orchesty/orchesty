<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Repository;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Crypt\Exceptions\CryptException;
use Hanaboso\CommonsBundle\WorkerApi\ClientInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\DocumentAbstract;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository;

/**
 * Class ApplicationInstallRepository
 *
 * @extends Repository<ApplicationInstall>
 *
 * @package Hanaboso\PipesPhpSdk\Application\Repository
 */
final class ApplicationInstallRepository extends Repository
{

    /**
     * ApplicationInstallRepository constructor.
     *
     * @param ClientInterface $client
     * @param CryptManager    $cryptManager
     */
    public function __construct(ClientInterface $client, private readonly CryptManager $cryptManager)
    {
        parent::__construct($client, ApplicationInstall::class);
    }

    /**
     * @param string $name
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function findUserApp(string $name, string $user): ApplicationInstall
    {
        /** @var ApplicationInstall|null $app */
        $app = $this->findOne(new ApplicationInstallFilter(names: [$name], users: [$user], deleted: FALSE));

        if (!$app) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was not found .', $name),
                ApplicationInstallException::APP_WAS_NOT_FOUND,
            );
        }

        return $app;
    }

    /**
     * @param string   $user
     * @param string[] $applications
     *
     * @return ApplicationInstall[]
     * @throws GuzzleException
     */
    public function findUserApps(string $user, array $applications): array
    {
        return $this->findMany(new ApplicationInstallFilter(names: $applications, users: [$user]));
    }

    /**
     * @param string $name
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    public function findOneByName(string $name): ApplicationInstall
    {
        /** @var ApplicationInstall|null $app */
        $app = $this->findOne(new ApplicationInstallFilter(names: [$name]));

        if (!$app) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was not found .', $name),
                ApplicationInstallException::APP_WAS_NOT_FOUND,
            );
        }

        return $app;
    }

    /**
     * @param DocumentAbstract $entity
     *
     * @return void
     * @throws CryptException
     */
    protected function beforeSend(DocumentAbstract $entity): void
    {
        /** @var ApplicationInstall $childEntity */
        $childEntity = $entity;
        if ($childEntity->getSettings()) {
            $childEntity->setEncryptedSettings($this->cryptManager->encrypt($childEntity->getSettings()));
            $childEntity->setSettings([]);
        }
    }

    /**
     * @param DocumentAbstract $entity
     *
     * @return void
     * @throws CryptException
     */
    protected function afterReceive(DocumentAbstract $entity): void
    {
        /** @var ApplicationInstall $childEntity */
        $childEntity = $entity;
        if ($childEntity->getEncryptedSettings()) {
            $childEntity->setSettings($this->cryptManager->decrypt($childEntity->getEncryptedSettings()));
            $childEntity->setEncryptedSettings('');
        }
    }

}
