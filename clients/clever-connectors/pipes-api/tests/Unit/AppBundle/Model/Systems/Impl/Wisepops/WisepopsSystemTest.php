<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
use Tests\KernelTestCaseAbstract;

/**
 * Class WisepopsSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops
 */
class WisepopsSystemTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetLimit(): void
    {
        $system = new WisepopsSystem(NULL);
        $sys    = new SystemInstall();
        $sys->setSystem($system->getKey())
            ->setSettings([
                SystemInstall::SYSTEM_LIMIT_VALUE => '400',
            ])
            ->setUser('user');

        $dto = $system->getLimit($sys);

        self::assertEquals([
            'pf-limit-value'    => 400,
            'pf-limit-key'      => 'wisepops|user',
            'pf-limit-time'     => 2592000,
            'limit-last-update' => $dto->getLastUpdate(),
        ], $dto->toArray());
    }

}