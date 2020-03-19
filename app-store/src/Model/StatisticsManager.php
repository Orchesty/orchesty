<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Model;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class StatisticsManager
 *
 * @package Hanaboso\HbPFAppStore\Model
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
        return $this->repository->getApplicationsCount() ?? [];
    }

    /**
     * @param string $application
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getApplicationsUsers(string $application): array
    {
        return $this->repository->getApplicationsCountDetails($application) ?? [];
    }

}
