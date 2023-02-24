<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class StatisticsManager
 *
 * @package Hanaboso\Applinth\Manager
 */
final class StatisticsManager
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
     * @throws DateTimeException
     */
    public function getApplicationsBasicData(): array
    {
        return $this->repository->getApplicationsCount();
    }

    /**
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getUsersBasicData(): array
    {
        return $this->repository->getUsersCount();
    }

    /**
     * @param string $application
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getApplicationsUsers(string $application): array
    {
        return $this->repository->getApplicationsCountDetails($application);
    }

}
