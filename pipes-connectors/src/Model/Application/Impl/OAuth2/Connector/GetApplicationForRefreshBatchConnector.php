<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class GetApplicationForRefreshBatchConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
final class GetApplicationForRefreshBatchConnector extends BatchAbstract
{

    public const NAME = 'get_application_for_refresh';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * GetApplicationForRefreshBatchConnector constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        /** @var ApplicationInstallRepository $appInstallRepo */
        $appInstallRepo   = $dm->getRepository(ApplicationInstall::class);
        $this->repository = $appInstallRepo;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param BatchProcessDto $dto
     *
     * @return BatchProcessDto
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        $time = DateTimeUtils::getUtcDateTime('1 hour');

        /** @var ApplicationInstall[] $applications */
        $applications = $this->repository
            ->createQueryBuilder()
            ->field('expires')->lte($time)
            ->field('expires')->notEqual(NULL)
            ->getQuery()
            ->execute();

        foreach ($applications as $app) {
            $dto->addItem([],$app->getUser());
        }

        return $dto;
    }

}
