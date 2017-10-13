<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Listeners;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Configurator\Event\ProcessStatusEvent;
use Tests\KernelTestCaseAbstract;

/**
 * Class ProgressCounterListenerTest
 *
 * @package Tests\Unit\AppBundle\Listeners
 */
class ProgressCounterListenerTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMissingData(): void
    {
        $prov = $this->container->get('cc.listener.progress_counter');
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $prov->updateStatus(new ProcessStatusEvent([]));
    }

}