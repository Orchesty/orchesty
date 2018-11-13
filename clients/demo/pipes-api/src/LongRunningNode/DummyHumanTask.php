<?php declare(strict_types=1);

namespace Demo\LongRunningNode;

use Bunny\Message;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Model\Impl\LongRunningNodeAbstract;

/**
 * Class DummyHumanTask
 *
 * @package Demo\LongRunningNode
 */
final class DummyHumanTask extends LongRunningNodeAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hbpf.long_running.dummy';
    }

    /**
     * @param Message $message
     *
     * @return LongRunningNodeData
     */
    public function beforeAction(Message $message): LongRunningNodeData
    {
        $data = LongRunningNodeData::fromMessage($message);
        $data->setAuditLogs(json_decode($data->getData(), TRUE));

        return $data;
    }

}