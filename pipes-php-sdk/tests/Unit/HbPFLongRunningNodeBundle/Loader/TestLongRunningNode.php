<?php declare(strict_types=1);

namespace Tests\Unit\HbPFLongRunningNodeBundle\Loader;

use Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl\LongRunningNodeAbstract;

/**
 * Class TestLongRunningNode
 *
 * @package Tests\Unit\HbPFLongRunningNodeBundle\Loader
 */
final class TestLongRunningNode extends LongRunningNodeAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return '';
    }

}