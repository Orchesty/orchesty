<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Mapper;

use Exception;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Mapper\Impl\NullMapper;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;

/**
 * Class MapperAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\Mapper
 */
final class MapperAbstractTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Mapper\MapperAbstract::getApplicationKey
     * @covers \Hanaboso\PipesPhpSdk\Mapper\MapperAbstract::setApplication
     * @covers \Hanaboso\PipesPhpSdk\Mapper\Impl\NullMapper::process
     */
    public function testGetApplicationKey(): void
    {
        $mapper = new NullMapper();

        $result = $mapper->process(['data']);
        self::assertEquals(['data'], $result);

        $key = $mapper->getApplicationKey();
        self::assertNull($key);

        $key = $mapper->setApplication(new TestNullApplication())->getApplicationKey();
        self::assertEquals('null-key', $key);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Mapper\MapperAbstract::getApplication

     * @throws Exception
     */
    public function testGetApplicationException(): void
    {
        $mapper = new NullMapper();
        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::MISSING_APPLICATION);
        $mapper->getApplication();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Mapper\MapperAbstract::getApplication

     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $mapper = new NullMapper();
        $mapper->setApplication(new TestNullApplication());
        self::assertNotEmpty($mapper->getApplication());
    }

}
