<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Listeners;

use Hanaboso\PipesFramework\Configurator\Event\ProcessStatusEvent;
use Tests\KernelTestCaseAbstract;

/**
 * Class ProgressCounterListenerTest
 *
 * @package Tests\Unit\AppBundle\Listeners
 */
final class ProgressCounterListenerTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMissingData(): void
    {
        $prov = $this->ownContainer->get('cc.progress_counter.listener');
        $prov->updateStatus(new ProcessStatusEvent('123456', FALSE));
    }

}