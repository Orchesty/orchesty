<?php declare(strict_types=1);

namespace Tests\Unit\Acl\Factory;

use Hanaboso\PipesFramework\Acl\Factory\MaskFactory;
use Tests\KernelTestCaseAbstract;

/**
 * Class MaskFactoryTest
 *
 * @package Tests\Unit\Acl\Factory
 */
class MaskFactoryTest extends KernelTestCaseAbstract
{

    /**
     * @covers MaskFactory::maskAction()
     */
    public function testMaskAction(): void
    {
        $fac = $this->container->get('hbpf.factory.mask');

        $data = [
            'read'   => FALSE,
            'write'  => 1,
            'delete' => 'true',
        ];

        self::assertEquals(6, $fac->maskAction($data));
    }

    /**
     * @covers MaskFactory::maskProperty()
     */
    public function testMaskProperty(): void
    {
        $fac = $this->container->get('hbpf.factory.mask');

        $data = [
            'owner' => '1',
            'group' => 1,
        ];

        self::assertEquals(2, $fac->maskProperty($data));
    }

}