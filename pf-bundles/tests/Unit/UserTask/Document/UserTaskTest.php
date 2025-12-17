<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UserTask\Document;

use Exception;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\EnumException;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class UserTaskTest
 *
 * @package PipesFrameworkTests\Unit\UserTask\Document
 */
#[CoversClass(UserTask::class)]
final class UserTaskTest extends KernelTestCaseAbstract
{

    /**
     * @throws EnumException
     * @throws Exception
     */
    public function testDocument(): void
    {
        $userTask = new UserTask();
        $msg      = new UserTaskMessage();
        $this->setProperty($userTask, 'id', 'id');
        $userTask->preFlush();
        $userTask->postLoad();

        $date = DateTimeUtils::getUtcDateTimeFromTimeStamp(1_577_873_410);
        $this->setProperty($userTask, 'created', $date);
        $this->setProperty($userTask, 'updated', $date);

        $userTask->setReturnRoutingKey('rrk')
            ->setReturnExchange('re')
            ->setCorrelationId('cid')
            ->setType(UserTaskEnum::USER_TASK->value)
            ->setMessage($msg)
            ->setNodeName('node')
            ->setTopologyName('topo')
            ->setAuditLogs([])
            ->setTopologyId('tid')
            ->setNodeId('nid')
            ->addAuditLog(['a']);

        self::assertSame('rrk', $userTask->getReturnRoutingKey());
        self::assertSame('re', $userTask->getReturnExchange());
        self::assertSame('cid', $userTask->getCorrelationId());
        self::assertSame(UserTaskEnum::USER_TASK->value, $userTask->getType());
        self::assertSame($msg, $userTask->getMessage());
        self::assertSame('tid', $userTask->getTopologyId());
        self::assertSame('nid', $userTask->getNodeId());
        self::assertSame('node', $userTask->getNodeName());
        self::assertSame('topo', $userTask->getTopologyName());
        self::assertEquals(
            [
                UserTask::AUDIT_LOGS     => [['a']],
                UserTask::CORRELATION_ID => 'cid',
                UserTask::CREATED        => '2020-01-01T10:10:10Z',
                UserTask::ID             => 'id',
                UserTask::MESSAGE        => [
                    UserTaskMessage::BODY    => '',
                    UserTaskMessage::HEADERS => [],
                ],
                UserTask::NODE_ID        => 'nid',
                UserTask::NODE_NAME      => 'node',
                UserTask::TOPOLOGY_ID    => 'tid',
                UserTask::TOPOLOGY_NAME  => 'topo',
                UserTask::TYPE           => 'userTask',
                UserTask::UPDATED        => '2020-01-01T10:10:10Z',
                UserTask::USER           => NULL,
            ],
            $userTask->toArray(),
        );
    }

}
