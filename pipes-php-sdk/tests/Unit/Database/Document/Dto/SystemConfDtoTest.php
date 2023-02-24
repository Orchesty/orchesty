<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Database\Document\Dto;

use Exception;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Dto\SystemConfigDto;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class SystemConfDtoTest
 *
 * @package PipesPhpSdkTests\Unit\Database\Document\Dto
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

        $result = SystemConfigDto::fromString($json);

        self::assertEquals('Example', $result->getSdkHost());
        self::assertEquals(1, $result->getPrefetch());

        try {
            SystemConfigDto::fromString('example');
        } catch (Exception $e) {
            self::assertEquals('Syntax error', $e->getMessage());
        }
    }

}
