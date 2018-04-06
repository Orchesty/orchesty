<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\ServiceSystemManager;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;

/**
 * Class ServiceSystemManagerTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems
 */
final class ServiceSystemManagerTest extends TestCase
{

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\ServiceSystemManager::runCustomSystemAction()
     *
     * @throws \CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException
     */
    public function testRunCustomSystemAction(): void
    {
        $system  = new NullSystem();
        $manager = $this->createManager($system);

        $this->assertEquals(
            ['foo' => 'bar', 'processed' => TRUE, 'user' => 'unknown'],
            $manager->runCustomSystemAction('test', 'customAction', ['foo' => 'bar'])
        );
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Systems\ServiceSystemManager::runCustomSystemAction()
     *
     * @throws \CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException
     */
    public function testRunCustomSystemActionOnMissingSystemAction(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_METHOD_NOT_FOUND);

        $system  = $this->getMockBuilder(AuthorizationInterface::class)->getMock();
        $manager = $this->createManager($system);

        $manager->runCustomSystemAction('test', 'methodName', []);
    }

    /**
     * @param object $system
     *
     * @return ServiceSystemManager
     */
    private function createManager($system): ServiceSystemManager
    {
        /** @var DocumentManager|MockObject $dm */
        $dm = $this->getMockBuilder(DocumentManager::class)->disableOriginalConstructor()->getMock();
        $dm->method('flush')->willReturn(NULL);

        /** @var SystemLoader|MockObject $loader */
        $loader = $this->getMockBuilder(SystemLoader::class)->disableOriginalConstructor()->getMock();
        $loader->method('getSystem')->willReturn($system);

        return new ServiceSystemManager($dm, $loader);
    }

}
