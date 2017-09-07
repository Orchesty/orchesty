<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 4:04 PM
 */

namespace Tests\Utils;

use Hanaboso\PipesFramework\Commons\Node\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;
use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyFixtures
 *
 * @package Tests\Utils
 */
class TopologyFixtures extends DatabaseTestCaseAbstract
{

    /**
     * @todo temporary test
     */
    public function testTopology(): void
    {
        $topology = new Topology();
        $topology->setName('topology');
        $this->dm->persist($topology);
        $this->dm->flush();

        $node1 = new Node();
        $node1
            ->setName('magento2_customer')
            ->setTopology($topology->getId());
        $this->dm->persist($node1);

        $node2 = new Node();
        $node2
            ->setName('xml_parser')
            ->setTopology($topology->getId());
        $this->dm->persist($node2);

        $node3 = new Node();
        $node3
            ->setName('filter_1')
            ->setTopology($topology->getId());
        $this->dm->persist($node3);

        $node4 = new Node();
        $node4
            ->setName('mail')
            ->setTopology($topology->getId());
        $this->dm->persist($node4);

        $node5 = new Node();
        $node5
            ->setName('ftp')
            ->setTopology($topology->getId());
        $this->dm->persist($node5);

        $node6 = new Node();
        $node6
            ->setName('api')
            ->setTopology($topology->getId());
        $this->dm->persist($node6);

        $node1->addNext(EmbedNode::from($node2));

        $node2->addNext(EmbedNode::from($node3));

        $node3->addNext(EmbedNode::from($node4));
        $node3->addNext(EmbedNode::from($node5));
        $node3->addNext(EmbedNode::from($node6));

        $this->dm->flush();
    }

}