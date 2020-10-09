<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class RefreshOAuth2TokenBatchConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
final class RefreshOAuth2TokenBatchConnector extends ConnectorAbstract implements BatchInterface
{

    use ProcessActionNotSupportedTrait;
    use ProcessEventNotSupportedTrait;
    use BatchTrait;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var ApplicationLoader
     */
    private ApplicationLoader $loader;

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * RefreshOAuth2TokenBatchConnector constructor.
     *
     * @param DocumentManager   $dm
     * @param ApplicationLoader $loader
     */
    public function __construct(DocumentManager $dm, ApplicationLoader $loader)
    {
        $this->dm         = $dm;
        $this->repository = $dm->getRepository(ApplicationInstall::class);
        $this->loader     = $loader;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'refresh_oauth2_token';
    }

    /**
     * @param ProcessDto $dto
     *
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws DateTimeException
     * @throws MongoDBException
     * @throws MappingException
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $callbackItem;

        $applicationId = PipesHeaders::get(GetApplicationForRefreshBatchConnector::APPLICATION_ID, $dto->getHeaders());
        /** @var ApplicationInstall|null $applicationInstall */
        $applicationInstall = $this->repository->find($applicationId);

        if ($applicationInstall) {
            /** @var OAuth2ApplicationAbstract $application */
            $application = $this->loader->getApplication($applicationInstall->getKey());
            $application->refreshAuthorization($applicationInstall);
            $this->dm->flush();
        }

        return $this->createPromise();
    }

}
