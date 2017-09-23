<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.9.17
 * Time: 11:27
 */

namespace Tests\Unit\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class GeneratorHandlerTest
 *
 * @package Tests\Unit\HbPFConfiguratorBundle\Handler
 */
class GeneratorHandlerTest extends TestCase
{

    /**
     * @covers GeneratorHandler::generateTopology()
     */
    public function testGenerateTopologyOnExists()
    {
        $repositoryTopology = $this->createMock(DocumentRepository::class);
        $repositoryTopology->expects($this->at(0))->method('find')->willReturn(new Topology());

        $repositoryNode = $this->createMock(DocumentRepository::class);
        $repositoryNode->expects($this->at(0))->method('findBy')->willReturn(new Node());

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            function ($class) use ($repositoryTopology, $repositoryNode): DocumentRepository {
                if ($class == Topology::class) {

                    return $repositoryTopology;
                } elseif ($class == Node::class) {

                    return $repositoryNode;
                }
            });

        /** @var PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->getMockBuilder(GeneratorHandler::class)
            ->setConstructorArgs([$dm, '/srv/directory', 'demo_network'])
            ->setMethods(['generate'])
            ->getMock();

        $handler->expects($this->once())->method('generate');

        /** @var GeneratorHandler $handler */
        $this->assertTrue($handler->generateTopology("ABCD123456"));
    }

    /**
     * @covers GeneratorHandler::generateTopology()
     */
    public function testGenerateTopologyNonExists()
    {
        $repositoryTopology = $this->createMock(DocumentRepository::class);
        $repositoryTopology->expects($this->at(0))->method('find')->willReturn(new Topology());

        $repositoryNode = $this->createMock(DocumentRepository::class);
        $repositoryNode->expects($this->at(0))->method('findBy')->willReturn(NULL);

        /** @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturnCallback(
            function ($class) use ($repositoryTopology, $repositoryNode): DocumentRepository {
                if ($class == Topology::class) {

                    return $repositoryTopology;
                } elseif ($class == Node::class) {

                    return $repositoryNode;
                }
            });

        /** @var PHPUnit_Framework_MockObject_MockObject $handler */
        $handler = $this->getMockBuilder(GeneratorHandler::class)
            ->setConstructorArgs([$dm, '/srv/directory', 'demo_network'])
            ->setMethods(['generate'])
            ->getMock();

        $handler->expects($this->never())->method('generate');

        /** @var GeneratorHandler $handler */
        $this->assertFalse($handler->generateTopology("ABCD123456"));
    }

}
