<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\BasecrmSystem;
use Tests\KernelTestCaseAbstract;

/**
 * Class BasecrmSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm
 */
class BasecrmSystemTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetLimits(): void
    {
        $sys = new BasecrmSystem();
        $dto = $sys->getLimit((new SystemInstall())->setSystem($sys->getKey()));

        self::assertEquals([
            'pf-limit-value'    => 36000,
            'pf-limit-time'     => 3600,
            'pf-limit-key'      => 'basecrm',
            'limit-last-update' => $dto->getLastUpdate(),
        ], $dto->toArray());
    }

}