<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\ShipstationSystem;
use Tests\KernelTestCaseAbstract;

/**
 * Class ShipstationSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation
 */
final class ShipstationSystemTest extends KernelTestCaseAbstract
{

    /**
     * @var ShipstationSystem
     */
    private $system;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->system        = new ShipstationSystem();
        $this->systemInstall = (new SystemInstall())
            ->setSystem($this->system->getKey())
            ->setUser('user');
    }

    /**
     *
     */
    public function testGetLimit(): void
    {
        $data = $this->system->getLimit($this->systemInstall)->toArray();
        unset($data['limit-last-update']);

        $this->assertEquals([
            'pf-limit-key'   => 'shipstation|user',
            'pf-limit-value' => 40,
            'pf-limit-time'  => 60,
        ], $data);
    }

    /**
     *
     */
    public function testSaveLimit(): void
    {
        $this->assertInstanceOf(SystemInstall::class, $this->system->saveLimit($this->systemInstall, []));
    }

}