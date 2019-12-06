<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Model;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;

/**
 * Class StatisticsManager
 *
 * @package Hanaboso\HbPFAppStore\Model
 */
class StatisticsManager
{

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    private $repository;

    /**
     * StatisticsManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @return mixed[]
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function getApplicationsBasicData(): array
    {
        return $this->repository->getApplicationsCount() ?? [];
    }

    /**
     * @param string $application
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function getApplicationsUsers(string $application): array
    {
        return $this->repository->getApplicationsCountDetails($application) ?? [];
    }

}