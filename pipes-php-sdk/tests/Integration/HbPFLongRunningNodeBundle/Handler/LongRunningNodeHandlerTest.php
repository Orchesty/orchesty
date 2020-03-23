<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFLongRunningNodeBundle\Handler;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\Utils\System\PipesHeaders;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFLongRunningNodeBundle\Handler
 */
final class LongRunningNodeHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var LongRunningNodeHandler
     */
    private $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::process
     *
     * @throws Exception
     */
    public function testProcess(): void
    {
        $node = new LongRunningNodeData();
        $this->pfd($node);
        $dto = $this->handler->process(
            'null',
            ['data' => 'data'],
            [
                PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER) => $node->getId(),
                PipesHeaders::createKey(PipesHeaders::PF_STOP)                   => '200',
            ]
        );

        self::assertEquals(['pf-result-code' => '200'], $dto->getHeaders());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::process
     *
     * @throws Exception
     */
    public function testProcessErr(): void
    {
        self::expectException(LongRunningNodeException::class);
        self::expectExceptionCode(LongRunningNodeException::LONG_RUNNING_DOCUMENT_NOT_FOUND);
        $this->handler->process(
            'null',
            ['data' => 'data'],
            [
                PipesHeaders::createKey(LongRunningNodeData::DOCUMENT_ID_HEADER) => '1',
                PipesHeaders::createKey(PipesHeaders::PF_STOP)                   => '200',
            ]
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::test
     */
    public function testTest(): void
    {
        $result = $this->handler->test('null');

        self::assertEquals([], $result);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::getTasksById
     *
     * @throws Exception
     */
    public function testGetTasksById(): void
    {
        $result = $this->handler->getTasksById(new GridRequestDto([]), '1', 'null');
        self::assertEquals(
            [
                'limit'  => 10,
                'offset' => 0,
                'count'  => 0,
                'total'  => 0,
                'items'  => [],
            ],
            $result
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::getTasks

     * @throws Exception
     */
    public function testGetTasks(): void
    {
        $result = $this->handler->getTasks(new GridRequestDto([]), '1', 'null');
        self::assertEquals(
            [
                'limit'  => 10,
                'offset' => 0,
                'count'  => 0,
                'total'  => 0,
                'items'  => [],
            ],
            $result
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::getAllLongRunningNodes
     */
    public function testGetAllLongRunningNodes(): void
    {
        self::assertEquals(['null'], $this->handler->getAllLongRunningNodes());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::updateLongRunningNode
     */
    public function testUpdateRunningNode(): void
    {
        $node = new LongRunningNodeData();
        $this->pfd($node);

        $result = $this->handler->updateLongRunningNode($node->getId(), ['data' => ['foo' => 'bar']]);
        self::assertEquals('{"foo":"bar"}', $result['data']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Handler\LongRunningNodeHandler::updateLongRunningNode
     */
    public function testUpdateRunningNodeErr(): void
    {
        self::expectException(LongRunningNodeException::class);
        self::expectExceptionCode(LongRunningNodeException::LONG_RUNNING_DOCUMENT_NOT_FOUND);
        $this->handler->updateLongRunningNode('123', ['data' => ['foo' => 'bar']]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::$container->get('hbpf.handler.long_running');
    }

}
