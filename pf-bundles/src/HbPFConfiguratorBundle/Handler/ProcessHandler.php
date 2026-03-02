<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Exception;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Model\ProcessManager;

/**
 * Class ProcessHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final readonly class ProcessHandler
{

    use GridHandlerTrait;

    /**
     * ProcessHandler constructor.
     *
     * @param ProcessManager $manager
     */
    public function __construct(private ProcessManager $manager)
    {
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getProcesses(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getProcesses($dto));
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getProcessesTotal(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getProcessesTotal($dto));
    }

    /**
     * @param GridRequestDtoInterface $dto
     * @param int                     $buckets
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getProcessesGraph(GridRequestDtoInterface $dto, int $buckets): array
    {
        return $this->getGridResponse($dto, $this->manager->getProcessesGraph($dto, $buckets));
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getProcessesTopologies(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getProcessesTopologies($dto));
    }

}
