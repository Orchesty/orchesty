<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;
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
        $this->loader = $this->container->get('cc.systems.loader');
    }

    /**
     *
     */
    public function testGetSystem(): void
    {
        $this->assertInstanceOf(NullSystem::class, $this->loader->getSystem('null.user'));
        $this->assertInstanceOf(NullSystem::class, $this->loader->getSystem('null.group'));
        $this->assertInstanceOf(NullSystem::class, $this->loader->getSystem('null.user.group'));
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
    public function testGetSystemsByUser(): void
    {
        $this->assertEquals(2, count($this->loader->getSystems('someUser')));
    }

    /**
     *
     */
    public function testGetSystemsByUserNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->loader->getSystems('unknown');
    }

    /**
     *
     */
    public function testGetSystemsByGroup(): void
    {
        $this->assertEquals(2, count($this->loader->getSystems(NULL, 'someGroup')));
    }

    /**
     *
     */
    public function testGetSystemsByGroupNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->loader->getSystems(NULL, 'unknown');
    }

    /**
     *
     */
    public function testGetSystemsByUserAndGroup(): void
    {
        $this->assertEquals(1, count($this->loader->getSystems('someUser', 'someGroup')));
    }

    /**
     *
     */
    public function testGetSystemsBySystems(): void
    {
        $this->assertEquals(9, count($this->loader->getSystems()));
    }

}