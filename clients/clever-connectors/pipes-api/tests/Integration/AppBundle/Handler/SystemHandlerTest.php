<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\SystemHandler;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SystemHandlerTest
 *
 * @package Tests\Integration\AppBundle\Handler
 */
class SystemHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testInstallSystem(): void
    {
        $dm = $this->container->get('doctrine_mongodb.odm.default_document_manager');

        $manager = $this->getMockBuilder(SystemManager::class)->disableOriginalConstructor()->getMock();
        $manager->method('installSystem')->willReturn(new SystemInstall());
        $manager->method('getUserSystem')->willReturn([]);

        $handler = new SystemHandler($manager, $dm);
        $handler->installSystem('user', 'null', ['token' => 'token']);

        $sys = new SystemInstall();
        $sys->setUser('user')->setSystem('null');
        $dm->persist($sys);
        $dm->flush($sys);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::SYSTEM_ALREADY_INSTALLED);
        $handler->installSystem('user', 'null', ['token' => 'token']);
    }

    /**
     *
     */
    public function testInstallSystemMissingToken(): void
    {
        $handler = $this->container->get('cc.systems.handler');
        $this->expectException(PipesFrameworkException::class);
        $this->expectExceptionCode(PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND);
        $handler->installSystem('', '', []);
    }

}