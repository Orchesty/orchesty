<?php declare(strict_types=1);

namespace Demo\LongRunningNode;

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

}