<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Builder;

use Exception;
use Hanaboso\PipesFramework\Metrics\Builder\Builder;
use InfluxDB\Database;
use InvalidArgumentException;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class BuilderTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Builder
 */
final class BuilderTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Metrics\Builder\Builder::parseQuery
     *
     * @throws Exception
     */
    public function testParseQuery(): void
    {
        $mock    = self::createPartialMock(Database::class, []);
        $builder = new Builder($mock);

        $this->setProperty($builder, 'metric', 'metric');
        $this->setProperty($builder, 'retentionPolicy', 'rentalPolicy');
        $this->setProperty($builder, 'groupBy', ['foo', 'bar']);
        $this->setProperty($builder, 'orderBy', ['foo', 'bar']);
        $this->setProperty($builder, 'limitClause', 'soemthing');
        $result = $this->invokeMethod($builder, 'parseQuery');
        self::assertEquals('SELECT * FROM "rentalPolicy"."metric" GROUP BY foo,bar ORDER BY foo,barsoemthing', $result);

        $this->setProperty($builder, 'metric', NULL);
        self::expectException(InvalidArgumentException::class);
        $this->invokeMethod($builder, 'parseQuery');
    }

}
