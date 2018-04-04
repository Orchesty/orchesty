<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Aim;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AimSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Aim
 */
final class AimSystemTest extends TestCase
{

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem::runSync()
     * @throws \Exception
     */
    public function testRunSync(): void
    {
        /** @var StartingPointHandler|MockObject $start */
        $start = $this->getMockBuilder(StartingPointHandler::class)->disableOriginalConstructor()->getMock();
        $start->method('runWithRequest')->willReturnCallback(function (Request $request, $topo, $node): void {
            $this->assertEquals(AimSystem::SYNC_TOPO, $topo);
            $this->assertEquals(AimSystem::SYNC_NODE, $node);
            $this->assertEquals(
                AimSystem::SYNC_ACTION,
                $request->headers->get(CMHeaders::createKey(AimSystem::HEADER_ACTION))
            );
            $this->assertEquals(
                AimSystem::DESTINATION_AMERICA,
                $request->headers->get(CMHeaders::createKey(AimSystem::HEADER_DESTINATION))
            );
        });

        $aim    = new AimSystem($start);
        $result = $aim->runSync($this->getData());

        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem::runSync()
     * @throws \Exception
     */
    public function testRunSyncFailsOnInvalidDestination(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::INVALID_DATA);

        /** @var StartingPointHandler|MockObject $start */
        $start = $this->getMockBuilder(StartingPointHandler::class)->disableOriginalConstructor()->getMock();
        $start->method('runWithRequest')->willReturn(NULL);

        $data = $this->getData();
        array_push($data['destinations'], 'Antarctida');

        $aim = new AimSystem($start);
        $aim->runSync($data);

    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem::runDelete()
     * @throws \Exception
     */
    public function testRunDelete(): void
    {
        /** @var StartingPointHandler|MockObject $start */
        $start = $this->getMockBuilder(StartingPointHandler::class)->disableOriginalConstructor()->getMock();
        $start->method('runWithRequest')->willReturnCallback(function (Request $request, $topo, $node): void {
            $this->assertEquals(AimSystem::DELETE_TOPO, $topo);
            $this->assertEquals(AimSystem::DELETE_NODE, $node);
            $this->assertEquals(
                AimSystem::DELETE_ACTION,
                $request->headers->get(CMHeaders::createKey(AimSystem::HEADER_ACTION))
            );
            $this->assertEquals(
                AimSystem::DESTINATION_AMERICA,
                $request->headers->get(CMHeaders::createKey(AimSystem::HEADER_DESTINATION))
            );
        });

        $aim    = new AimSystem($start);
        $result = $aim->runDelete($this->getData());

        $this->assertTrue($result);
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        return [
            'category'     => 'Template',
            'destinations' => [AimSystem::DESTINATION_AMERICA],
            'data'         => [
                'sku' => 'abc-11',
            ],
        ];
    }

}
