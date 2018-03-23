<?php declare(strict_types=1);

namespace Tests\Integration\Models;

use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Models\AdModuleInterface;
use CleverCore\SocialMultichannel\Models\AdModuleLoader;
use LogicException;
use Nette\DI\MissingServiceException;
use Tests\ContainerTestCaseAbstract;

/**
 * Class AdModuleLoaderTest
 *
 * @package Tests\Integration\Model
 */
class AdModuleLoaderTest extends ContainerTestCaseAbstract
{

    /**
     *
     */
    public function testLoadModule(): void
    {
        $adModule = $this->createMock(AdModuleInterface::class);
        $this->container->addService('test_module.fb', $adModule);

        $adModuleLoader = new AdModuleLoader($this->container, 'test_module');

        $this->assertSame($adModule, $adModuleLoader->loadModule(AdTypeEnum::FB));
    }

    /**
     *
     */
    public function testLoadModuleNotFound(): void
    {
        $this->expectException(MissingServiceException::class);

        $adModule = $this->createMock(AdModuleInterface::class);
        $this->container->addService('test_module.fb', $adModule);

        $adModuleLoader = new AdModuleLoader($this->container, 'test_module');

        $adModuleLoader->loadModule(AdTypeEnum::TWITTER);
    }

    /**
     *
     */
    public function testLoadModuleInvalidType(): void
    {
        $this->expectException(LogicException::class);

        $adModule = $this->createMock(AdModuleInterface::class);
        $this->container->addService('test_module.fb', $adModule);

        $adModuleLoader = new AdModuleLoader($this->container, 'test_module');

        $adModuleLoader->loadModule('invalid_type');
    }

}