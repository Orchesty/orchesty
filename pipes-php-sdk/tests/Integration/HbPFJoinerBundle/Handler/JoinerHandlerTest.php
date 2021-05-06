<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFJoinerBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class JoinerHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFJoinerBundle\Handler
 */
final class JoinerHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var JoinerHandler
     */
    private JoinerHandler $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler::processJoiner
     *
     * @throws Exception
     */
    public function testProcessJoiner(): void
    {
        self::assertEquals([], $this->handler->processJoiner('null', ['data' => ['data'], 'count' => 2]));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler::processJoinerTest
     *
     * @throws Exception
     */
    public function testProcessJoinerTest(): void
    {
        $this->handler->processJoinerTest('null', ['data' => 'bar', 'count' => 2]);
        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler::processJoinerTest
     *
     * @throws Exception
     */
    public function testProcessJoinerTestErr(): void
    {
        self::expectException(JoinerException::class);
        self::expectExceptionMessage("Data under 'data' key are missing in request.");
        $this->handler->processJoinerTest('null', ['foo' => 'bar']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler::processJoinerTest
     *
     * @throws Exception
     */
    public function testProcessJoinerTestErr2(): void
    {
        self::expectException(JoinerException::class);
        self::expectExceptionMessage("Total data count under 'count' key is missing in request.");
        $this->handler->processJoinerTest('null', ['data' => 'bar']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler::getJoiners
     */
    public function testGetJoiners(): void
    {
        self::assertEquals(['null'], $this->handler->getJoiners());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::$container->get('hbpf.handler.joiner');
    }

}
