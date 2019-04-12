<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Model\Dto;

use Hanaboso\PipesFramework\Configurator\Model\Dto\SystemConfDto;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Tests\KernelTestCaseAbstract;

/**
 * Class SystemConfDtoTest
 *
 * @package Tests\Unit\Configurator\Model\Dto
 */
final class SystemConfDtoTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testToString(): void
    {
        $dto  = new SystemConfDto();
        $json = $dto->toString(TopologySchemaUtils::$confFields);

        self::assertJson($json);
    }

    /**
     *
     */
    public function testFromString(): void
    {
        $dto  = new SystemConfDto('Example');
        $json = $dto->toString(TopologySchemaUtils::$confFields);

        $result = $dto->fromString($json, TopologySchemaUtils::$confFields);

        self::assertEquals('Example', $result->getSdkHost());
        self::assertEquals(1, $result->getPrefetch());
    }

}