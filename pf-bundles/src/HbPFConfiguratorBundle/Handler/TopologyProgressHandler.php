<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Model\ProgressManager;

/**
 * Class TopologyProgressHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class TopologyProgressHandler
{

    use GridHandlerTrait;

    /**
     * TopologyProgressHandler constructor.
     *
     * @param ProgressManager $manager
     */
    public function __construct(private ProgressManager $manager)
    {
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getProgress(GridRequestDtoInterface $dto): array
    {
        $items = $this->manager->getProgress($dto);

        return $this->getGridResponse($dto, $items);
    }

}
