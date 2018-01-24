<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\WebhookController;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Listeners\WebhookSecurityListener;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Tests\KernelTestCaseAbstract;

/**
 * Class WebhookSecurityListenerTest
 *
 * @package Tests\Unit\AppBundle\Listeners
 */
final class WebhookSecurityListenerTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testCheckSecurity(): void
    {
        $systemInstall = new SystemInstall();

        /** @var WebhookController|PHPUnit_Framework_MockObject_MockObject $repository */
        $controller = $this->createMock(WebhookController::class);

        /** @var DocumentRepository|PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(DocumentRepository::class);
        $repository->method('findOneBy')->willReturn((new Webhook())->setSystemKey('systemKey'));

        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->at(0))->method('getRepository')->willReturn($repository);
        $documentManager->expects($this->at(1))->method('getRepository')->willReturn($systemInstallRepository);

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')->willReturn(new ResponseDto(200, '', '', []));

        /** @var FilterControllerEvent|PHPUnit_Framework_MockObject_MockObject $controllerEvent */
        $controllerEvent = $this->createMock(FilterControllerEvent::class);
        $controllerEvent->method('getController')->willReturn([$controller]);
        $controllerEvent->method('getRequest')->willReturn(new Request([], [], [
            'nodeName'     => 'nodeName',
            'topologyName' => 'topologyName',
            'token'        => 'token',
            'userId'       => 'userId',
        ]));

        /** @var SystemLimitManager|MockObject $systemLimitManager */
        $systemLimitManager = $this->createMock(SystemLimitManager::class);
        $systemLimitManager->method('addSystemLimitToRequestHeaders');

        /** @var SystemLoader|MockObject $systemLoader */
        $systemLoader = $this->createMock(SystemLoader::class);

        $security = new WebhookSecurityListener($documentManager, $curlManager, $systemLimitManager, $systemLoader);
        $security->checkSecurity($controllerEvent);
        $headers = $controllerEvent->getRequest()->headers;

        $this->assertEquals('userId', $headers->get('pf-guid'));
        $this->assertEquals('token', $headers->get('pf-token'));
        $this->assertEquals('systemKey', $headers->get('pf-system-key'));
    }

}