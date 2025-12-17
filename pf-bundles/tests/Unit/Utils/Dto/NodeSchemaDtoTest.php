<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Utils\Dto;

use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class NodeSchemaDtoTest
 *
 * @package PipesFrameworkTests\Unit\Utils\Dto
 */
#[CoversClass(NodeSchemaDto::class)]
final class NodeSchemaDtoTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testNodeSchemaDto(): void
    {
        $dto = new NodeSchemaDto('handler', 'id', 'pipes', new SystemConfigDto(), 'name');

        self::assertSame(1, $dto->getSystemConfigs()->getPrefetch());
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
