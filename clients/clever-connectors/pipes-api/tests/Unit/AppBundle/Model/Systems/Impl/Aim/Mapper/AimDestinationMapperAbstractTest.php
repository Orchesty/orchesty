<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Aim\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper\AimEuropeMapper;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AimDestinationMapperAbstractTest extends TestCase
{

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper\AimDestinationMapperAbstract::process()
     *
     * @throws \CleverConnectors\AppBundle\Exceptions\CleverConnectorsException
     */
    public function testProcessOnMissingDestination(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $dto = new ProcessDto();
        $dto->setData(json_encode(['foo' => 'bar']));

        $mapper = new AimEuropeMapper();
        $mapper->process($dto);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper\AimDestinationMapperAbstract::process()
     *
     * @throws \CleverConnectors\AppBundle\Exceptions\CleverConnectorsException
     */
    public function testProcessOnAllDestinations(): void
    {
        $dto = new ProcessDto();
        $dto->addHeader('foo', 'bar');
        $dto->setData(json_encode([AimSystem::DATA_KEY_DESTINATIONS => [AimSystem::DESTINATION_ALL]]));

        $mapper = new AimEuropeMapper();
        $result = $mapper->process($dto);

        $this->assertSame($result, $dto);
        $this->assertEquals(['foo' => 'bar'], $dto->getHeaders());
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper\AimDestinationMapperAbstract::process()
     *
     * @throws \CleverConnectors\AppBundle\Exceptions\CleverConnectorsException
     */
    public function testProcessOnListedDestinations(): void
    {
        $dto = new ProcessDto();
        $dto->addHeader('foo', 'bar');
        $dto->setData(json_encode([
            AimSystem::DATA_KEY_DESTINATIONS => [
                AimSystem::DESTINATION_EUROPE,
                AimSystem::DESTINATION_ASIA,
            ],
        ]));

        $mapper = new AimEuropeMapper();
        $result = $mapper->process($dto);

        $this->assertSame($result, $dto);
        $this->assertEquals(['foo' => 'bar'], $dto->getHeaders());
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper\AimDestinationMapperAbstract::process()
     *
     * @throws \CleverConnectors\AppBundle\Exceptions\CleverConnectorsException
     */
    public function testProcessOnSkippedDestination(): void
    {
        $dto = new ProcessDto();
        $dto->addHeader('foo', 'bar');
        $dto->setData(json_encode([AimSystem::DATA_KEY_DESTINATIONS => [AimSystem::DESTINATION_ASIA]]));

        $mapper = new AimEuropeMapper();
        $result = $mapper->process($dto);

        $this->assertSame($result, $dto);
        $this->assertEquals([CMHeaders::createKey('result-code') => 1003], $dto->getHeaders());
    }

}
