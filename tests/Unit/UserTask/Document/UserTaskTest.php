<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\UserTask\Document;

use Exception;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\EnumException;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class UserTaskTest
 *
 * @package PipesFrameworkTests\Unit\UserTask\Document
 *
 * @covers  \Hanaboso\PipesFramework\UserTask\Document\UserTask
 */
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

        self::assertEquals('rrk', $userTask->getReturnRoutingKey());
        self::assertEquals('re', $userTask->getReturnExchange());
        self::assertEquals('cid', $userTask->getCorrelationId());
        self::assertEquals(UserTaskEnum::USER_TASK->value, $userTask->getType());
        self::assertEquals($msg, $userTask->getMessage());
        self::assertEquals('tid', $userTask->getTopologyId());
        self::assertEquals('nid', $userTask->getNodeId());
        self::assertEquals('node', $userTask->getNodeName());
        self::assertEquals('topo', $userTask->getTopologyName());
        self::assertEquals(
            [
                UserTask::ID             => 'id',
                UserTask::NODE_ID        => 'nid',
                UserTask::TOPOLOGY_ID    => 'tid',
                UserTask::TYPE           => 'userTask',
                UserTask::CORRELATION_ID => 'cid',
                UserTask::MESSAGE        => [
                    UserTaskMessage::BODY    => '',
                    UserTaskMessage::HEADERS => [],
                ],
                UserTask::AUDIT_LOGS     => [['a']],
                UserTask::TOPOLOGY_NAME  => 'topo',
                UserTask::NODE_NAME      => 'node',
                UserTask::CREATED        => '2020-01-01T10:10:10Z',
                UserTask::UPDATED        => '2020-01-01T10:10:10Z',
                UserTask::USER           => NULL,
            ],
            $userTask->toArray(),
        );
    }

}
