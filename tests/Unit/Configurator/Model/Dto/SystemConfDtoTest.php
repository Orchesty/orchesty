<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Model\Dto;

use Exception;
use Hanaboso\PipesFramework\Configurator\Model\Dto\SystemConfigDto;
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
        $dto  = new SystemConfigDto();
        $json = $dto->toString();

        self::assertJson($json);
    }

    /**
     * @throws Exception
     */
    public function testFromString(): void
    {
        $dto  = new SystemConfigDto('Example');
        $json = $dto->toString();

        $result = $dto->fromString($json);

        self::assertEquals('Example', $result->getSdkHost());
        self::assertEquals(1, $result->getPrefetch());

        try {
            $dto->fromString('example');
        } catch (Exception $e) {
            self::assertEquals($e->getMessage(), 'Syntax error');
        }
    }

}