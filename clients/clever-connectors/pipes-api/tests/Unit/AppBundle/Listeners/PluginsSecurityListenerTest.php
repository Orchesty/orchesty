<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\PluginsController;
use CleverConnectors\AppBundle\Listeners\PluginsSecurityListener;
use CleverConnectors\AppBundle\Model\Plugins\PluginsSecurityManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Tests\KernelTestCaseAbstract;

/**
 * Class PluginsSecurityListenerTest
 *
 * @package Tests\Unit\AppBundle\Listeners
 */
final class PluginsSecurityListenerTest extends KernelTestCaseAbstract
{

    /**
     * @covers PluginsSecurityListener::checkSecurity()
     */
    public function testCheckSecurity(): void
    {
        /** @var PluginsController|PHPUnit_Framework_MockObject_MockObject $repository */
        $controller = $this->createMock(PluginsController::class);

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')->willReturn(new ResponseDto(200, '', '', []));

        /** @var PluginsSecurityManager|PHPUnit_Framework_MockObject_MockObject $security */
        $security = $this->createMock(PluginsSecurityManager::class);

        $request          = new Request();
        $request->headers = new HeaderBag([
            'cm-token' => 'token',
            'cm-guid'  => 'userId',
        ]);

        /** @var FilterControllerEvent|PHPUnit_Framework_MockObject_MockObject $controllerEvent */
        $controllerEvent = $this->createMock(FilterControllerEvent::class);
        $controllerEvent->method('getController')->willReturn([$controller, 'installAction']);
        $controllerEvent->method('getRequest')->willReturn($request);

        $security = new PluginsSecurityListener($security, $curlManager);
        $security->checkSecurity($controllerEvent);
        $headers = $controllerEvent->getRequest()->headers;

        $this->assertEquals('userId', $headers->get('cm-guid'));
        $this->assertEquals('token', $headers->get('cm-token'));
    }

}