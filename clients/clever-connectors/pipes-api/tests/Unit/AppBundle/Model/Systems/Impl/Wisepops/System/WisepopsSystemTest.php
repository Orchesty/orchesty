<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\System;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class WisepopsSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\System
 */
final class WisepopsSystemTest extends ConnectorTestCaseAbstract
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

        $system = new WisepopsSystem($curl, $dm);
        $sys    = new SystemInstall();
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

        $res = $system->refreshForms($sys);

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

}