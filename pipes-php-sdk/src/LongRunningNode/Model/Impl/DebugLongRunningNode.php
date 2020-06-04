<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl;

/**
 * Class DebugLongRunningNode
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl
 */
final class DebugLongRunningNode extends LongRunningNodeAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'debug';
    }

}
