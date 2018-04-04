<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Aim\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector\AimConnectorAbstract;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AimConnectorAbstractTest extends TestCase
{

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector\AimConnectorAbstract::getId()
     */
    public function testGetId(): void
    {
        $this->assertEquals('aim-test', $this->createConnector()->getId());
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector\AimConnectorAbstract::processEvent()
     * @throws ConnectorException
     */
    public function testProcessEvent(): void
    {
        $this->expectException(ConnectorException::class);
        $this->expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);

        $this->createConnector()->processEvent(new ProcessDto());
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector\AimConnectorAbstract::processAction()
     *
     * @throws ConnectorException
     * @throws \Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException
     */
    public function testProcessInvalidAction(): void
    {
        $this->expectException(ConnectorException::class);
        $this->expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION);

        $dto = new ProcessDto();
        $dto->addHeader(CMHeaders::createKey(AimSystem::HEADER_ACTION), 'invalid_action');

        $this->createConnector()->processAction($dto);

    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector\AimConnectorAbstract::processAction()
     *
     * @throws ConnectorException
     * @throws \Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException
     */
    public function testProcessSyncAction(): void
    {
        $dto = new ProcessDto();
        $dto->addHeader(CMHeaders::createKey(AimSystem::HEADER_ACTION), AimSystem::SYNC_ACTION);

        $result = $this->createConnector()->processAction($dto);

        $this->assertSame($dto, $result);
        $this->assertEquals(['foo' => 'bar'], json_decode($result->getData(), TRUE));
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Connector\AimConnectorAbstract::processAction()
     *
     * @throws ConnectorException
     * @throws \Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException
     */
    public function testProcessDeleteAction(): void
    {
        $dto = new ProcessDto();
        $dto->addHeader(CMHeaders::createKey(AimSystem::HEADER_ACTION), AimSystem::SYNC_ACTION);

        $result = $this->createConnector()->processAction($dto);

        $this->assertSame($dto, $result);
        $this->assertEquals(['foo' => 'bar'], json_decode($result->getData(), TRUE));
    }

    /**
     * @return AimConnectorAbstract
     */
    private function createConnector(): AimConnectorAbstract
    {
        /** @var StartingPointHandler|MockObject $start */
        $start = $this->getMockBuilder(StartingPointHandler::class)->disableOriginalConstructor()->getMock();
        $aim = new AimSystem($start);
        /** @var CurlManagerInterface|MockObject $curl */
        $curl = $this->getMockBuilder(CurlManagerInterface::class)->getMock();
        $curl->method('send')->willReturn(new ResponseDto(0, 'OK', json_encode(['foo' => 'bar']), []));

        return new TestAimConnector($aim, $curl, 'test', 'localhost');
    }
    
}
