<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 13:30
 */

namespace Hanaboso\PipesFramework\CustomNode\Model\Batch;

use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Loader\CustomNodeLoader;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchActionAbstract;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use InvalidArgumentException;

/**
 * Class BatchActionCallback
 *
 * @package Hanaboso\PipesFramework\CustomNode\Model\Batch
 */
class BatchActionCallback extends BatchActionAbstract
{

    /**
     * @var CustomNodeLoader
     */
    private $customNodeLoader;

    /**
     * CronBatchActionCallback constructor.
     *
     * @param CustomNodeLoader $customNodeLoader
     */
    public function __construct(CustomNodeLoader $customNodeLoader)
    {
        parent::__construct();
        $this->customNodeLoader = $customNodeLoader;
    }

    /**
     * @param string $id
     *
     * @return BatchInterface
     */
    public function getBatchService(string $id): BatchInterface
    {
        /** @var BatchInterface $node */
        $node = $this->customNodeLoader->get($id);

        if (!$node instanceof BatchInterface) {
            throw new InvalidArgumentException(sprintf('The custom node not implemented "%s".', BatchInterface::class));
        }

        return $node;
    }

}