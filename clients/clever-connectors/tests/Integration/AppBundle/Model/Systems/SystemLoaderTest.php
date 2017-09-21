<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\NullSystem;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class SystemLoaderTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems
 */
class SystemLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = $this->container->get('systems.loader');
    }

    /**
     *
     */
    public function testGetSystem(): void
    {
        $this->assertInstanceOf(NullSystem::class, $this->loader->getSystem('null'));
    }

    /**
     *
     */
    public function testGetSystemNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $this->loader->getSystem('unknown');
    }

    /**
     *
     */
    public function testGetSystems(): void
    {
        $systems = $this->loader->getSystems('system');

        $this->assertNotEmpty($systems);
        $this->assertInstanceOf(NullSystem::class, $systems[0]);
    }

    /**
     *
     */
    public function testGetSystemsNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->loader->getSystems('unknown');
    }

}