<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector\WisepopsRefreshFormsConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class WisepopsRefreshFormsConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Connector
 */
final class WisepopsRefreshFormsConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testRefreshForms(): void
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto): ResponseDto {
                    $dto = new RequestDto('GET', new Uri(
                        'https://app.wisepops.com/api1/wisepops'
                    ));
                    $dto->setHeaders([
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'WISEPOPS-API key="apiKey"',

                    ]);

                    self::assertEquals($dto, $requestDto);

                    return new ResponseDto(200, '', $this->getRequest('FormsInfo.json'), []);
                }
            ));

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('flush')->willReturn([]);

        $conn = new WisepopsRefreshFormsConnector(
            $dm,
            $curl
        );
        $sys  = new SystemInstall();
        $sys->setSettings([
            'api_key'     => 'apiKey',
            'custom_form' => [
                [
                    'form_id'   => 106677,
                    'form_name' => 'vsfkdj',
                    'list'      => 'someList',
                ],
            ],
        ]);

        $res = $conn->refreshForms($sys);

        $expt = [
            [
                'form_id'   => 106677,
                'form_name' => 'vsfkdj',
                'list'      => 'someList',
            ],
            [
                'form_id'   => 106682,
                'form_name' => 'BF: Fashion',
                'list'      => NULL,
            ],
            [
                'form_id'   => 106625,
                'form_name' => 'Welcome Brand',
                'list'      => NULL,
            ],
        ];

        self::assertEquals($expt, $res);
    }

    /**
     *
     */
    public function testRefreshFormsLimit(): void
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $dto): void {
                    throw new CurlException(
                        '',
                        0,
                        NULL,
                        new Response(429)
                    );
                }
            ));

        $sys = new SystemInstall();
        $sys->setSettings([
            'api_key'     => 'apiKey',
            'custom_form' => [
                [
                    'form_id'   => 106677,
                    'form_name' => 'vsfkdj',
                    'list'      => 'someList',
                ],
            ],
        ]);

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        $conn = new WisepopsRefreshFormsConnector(
            $dm,
            $curl
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData('');

        $res = $conn->processAction($dto);

        self::assertArrayHasKey('pf-result-code', $res->getHeaders());
        self::assertEquals(1004, $res->getHeaders()['pf-result-code']);
    }

}