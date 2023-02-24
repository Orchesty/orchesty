<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository;

use Hanaboso\CommonsBundle\WorkerApi\ClientInterface;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Node;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository;

/**
 * Class NodeRepository
 *
 * @extends Repository<Node>
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository
 */
final class NodeRepository extends Repository
{

    /**
     * NodeRepository constructor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        parent::__construct($client, Node::class);
    }

}
