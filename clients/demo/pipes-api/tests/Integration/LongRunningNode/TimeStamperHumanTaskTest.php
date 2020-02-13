<?php declare(strict_types=1);

namespace DemoTests\Integration\LongRunningNode;

use Demo\LongRunningNode\TimeStamperHumanTask;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\Utils\String\Json;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class TimeStamperHumanTaskTest
 *
 * @package DemoTests\Integration\LongRunningNode
 */
final class TimeStamperHumanTaskTest extends KernelTestCaseAbstract
{

    /**
     * @var TimeStamperHumanTask
     */
    private TimeStamperHumanTask $node;

    /**
     * @covers \Demo\LongRunningNode\TimeStamperHumanTask::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('hbpf.long_running.time-stamper', $this->node->getId());
    }

    /**
     * @covers \Demo\LongRunningNode\TimeStamperHumanTask
     * @covers \Demo\LongRunningNode\TimeStamperHumanTask::createTimeStamp
     * @covers \Demo\LongRunningNode\TimeStamperHumanTask::beforeAction
     * @throws Exception
     */
    public function testBeforeAction(): void
    {
        $data = $this->node->beforeAction(new AMQPMessage('{"message":"something"}'));

        self::assertEquals('{"message":"something"}', $data->getData());
    }

    /**
     * @covers \Demo\LongRunningNode\TimeStamperHumanTask::afterAction
     * @throws Exception
     */
    public function testAfterAction(): void
    {
        $dto = $this->node->afterAction(new LongRunningNodeData(), []);

        self::assertArrayHasKey('timestamp', Json::decode($dto->getData()));
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->node = self::$container->get('hbpf.long_running.time-stamper');
    }

}
