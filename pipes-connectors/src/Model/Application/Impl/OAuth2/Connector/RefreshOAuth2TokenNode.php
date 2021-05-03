<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class RefreshOAuth2TokenNode
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
final class RefreshOAuth2TokenNode extends CustomNodeAbstract
{

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * RefreshOAuth2TokenNode constructor.
     *
     * @param DocumentManager   $dm
     * @param ApplicationLoader $loader
     */
    public function __construct(private DocumentManager $dm, private ApplicationLoader $loader)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
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
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws DateTimeException
     * @throws MappingException
     * @throws MongoDBException
     * @throws LockException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $applicationId = PipesHeaders::get(GetApplicationForRefreshBatchConnector::APPLICATION_ID, $dto->getHeaders());
        /** @var ApplicationInstall|null $applicationInstall */
        $applicationInstall = $this->repository->find($applicationId);

        if ($applicationInstall) {
            /** @var OAuth2ApplicationAbstract $application */
            $application = $this->loader->getApplication($applicationInstall->getKey());
            $application->refreshAuthorization($applicationInstall);
            $this->dm->flush();
        }

        return $dto;
    }

}
