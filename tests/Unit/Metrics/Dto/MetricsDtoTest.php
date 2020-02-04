<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Metrics\Dto;

use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class MetricsDtoTest
 *
 * @package PipesFrameworkTests\Unit\Metrics\Dto
 */
final class MetricsDtoTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::setMin
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::setMax
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::setAvg
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::setTotal
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::setErrors
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::getMin
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::getMax
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::getAvg
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::getTotal
     * @covers \Hanaboso\PipesFramework\Metrics\Dto\MetricsDto::getErrors
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
