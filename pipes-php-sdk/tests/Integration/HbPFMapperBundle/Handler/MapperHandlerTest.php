<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFMapperBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class MapperHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFMapperBundle\Handler
 */
final class MapperHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var MapperHandler
     */
    private $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler::process
     *
     * @throws Exception
     */
    public function testProcess(): void
    {
        $mapper = $this->handler->process('null', ['data']);

        self::assertEquals(['data'], $mapper);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler::processTest
     *
     * @throws Exception
     */
    public function testProcessTest(): void
    {
        $mapper = $this->handler->processTest('null', ['data']);
        self::assertEquals(['data'], $mapper);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::$container->get('hbpf.mapper.handler.mapper');
    }

}
