<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\OpenSourcePluginsController;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Listeners\OpenSourcePluginsSecurityListener;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use LogicException;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class OpenSourcePluginsSecurityListenerTest
 *
 * @package Tests\Integration\AppBundle\Listeners
 */
final class OpenSourcePluginsSecurityListenerTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testCheckSecurity(): void
    {
        $sys = new SystemInstall();
        $sys->setToken('tok')->setUser('usr')->setSystem('sys');
        $this->persistAndFlush($sys);

        $listener = new OpenSourcePluginsSecurityListener($this->container->get('cc.open_source_plugins.security_manager'));

        $request = new Request();
        $request->headers->set(CMHeaders::createKey(CMHeaders::GUID), 'usr');
        $request->headers->set(CMHeaders::createKey(CMHeaders::TOKEN), 'tok');
        $request->headers->set(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), 'sys');

        /** @var FilterControllerEvent|PHPUnit_Framework_MockObject_MockObject $ev */
        $ev = $this->createMock(FilterControllerEvent::class);
        $ev->method('getController')->willReturn([$this->createMock(OpenSourcePluginsController::class)]);
        $ev->method('getRequest')->willReturn($request);

        $listener->checkSecurity($ev);
    }

    /**
     *
     */
    public function testCheckSecurityNotFound(): void
    {
        $sys = new SystemInstall();
        $sys->setToken('tok')->setUser('usr')->setSystem('sys');
        $this->persistAndFlush($sys);

        $listener = new OpenSourcePluginsSecurityListener($this->container->get('cc.open_source_plugins.security_manager'));

        $request = new Request();
        $request->headers->set(CMHeaders::createKey(CMHeaders::GUID), 'usr456');
        $request->headers->set(CMHeaders::createKey(CMHeaders::TOKEN), 'tok');
        $request->headers->set(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), 'sys');

        /** @var FilterControllerEvent|PHPUnit_Framework_MockObject_MockObject $ev */
        $ev = $this->createMock(FilterControllerEvent::class);
        $ev->method('getController')->willReturn([$this->createMock(OpenSourcePluginsController::class)]);
        $ev->method('getRequest')->willReturn($request);

        $this->expectException(LogicException::class);

        $listener->checkSecurity($ev);
    }

}