<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Utils\Dto;

use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class NodeSchemaDtoTest
 *
 * @package PipesFrameworkTests\Unit\Utils\Dto
 */
final class NodeSchemaDtoTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto
     * @covers \Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto::getSystemConfigs
     * @covers \Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto::toArray
     */
    public function testNodeSchemaDto(): void
    {
        $dto = new NodeSchemaDto('handler', 'id', 'pipes', new SystemConfigDto(), 'name');

        self::assertEquals(1, $dto->getSystemConfigs()->getPrefetch());
        self::assertEquals(
            [
                'application'    => '',
                'cron_params'    => '',
                'cron_time'      => '',
                'handler'        => 'handler',
                'id'             => 'id',
                'name'           => 'name',
                'pipes_type'     => 'pipes',
                'system_configs' => new SystemConfigDto(),
            ],
            $dto->toArray(),
        );
    }

}
