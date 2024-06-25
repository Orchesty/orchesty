<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Metrics\Dto;

use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class MetricsDtoTest
 *
 * @package PipesFrameworkTests\Unit\Metrics\Dto
 */
#[CoversClass(MetricsDto::class)]
final class MetricsDtoTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testDto(): void
    {
        $dto = (new MetricsDto())
            ->setMin(0)
            ->setMax(0)
            ->setAvg(60, 180)
            ->setTotal(10)
            ->setErrors(10);

        self::assertEquals('0', $dto->getMin());
        self::assertEquals('0', $dto->getMax());
        self::assertEquals('3.00', $dto->getAvg());
        self::assertEquals('10', $dto->getTotal());
        self::assertEquals('10', $dto->getErrors());
    }

}
