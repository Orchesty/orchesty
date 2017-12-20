<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector\AirtableCreateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class AirtableCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableCreateContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers AirtableCreateContactConnector::processAction()
     */
    public function testProcessAction(): void
    {
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode([
                'fields' => [
                    'Name'  => 'abc',
                    'Email' => 'a@a.com',
                ],
            ]));

        /** @var AirtableCreateContactConnector $conn */
        $conn = $this->mockConn();
        $data = $conn->processAction($processDto);

        $this->assertEquals($this->getResponseData(), $data->getData());
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|AirtableCreateContactConnector
     */
    private function mockConn()
    {
        $a = '00_MUIDAN963H5M2ewkr-ItB1HncUlAUg6z1uAANgl1Wbk32HWq0MjrI6-SNi5zb7QiN-8AYXZUEZDVERD1B0ywKboQjYm3kJOf-RSrzcGv4ODRzZ8ZnMFII0BgZmXfNzm_ysWCg8Lm3r-8H8D8Gh9YjftUF4gaJ8TSu3PY2sSXWzN0PPO93Nan1Gy6KkBBvyi4-r9t4xIqyN0F3SXdncP6BkeBtiXb86btUeWD33yCoKwIfELy9ZAhtGblsF6Dj0ThLZ0G0Au5U9bwTZ6sgcnc4DBNt8rwEbz0nZ-BdqlAz7Mvaf7Kf6VFa4bLuLVLZUdB9oQHR_Pa5WxKuDydsy8XTjVdYnBwZiPqhUZVp-QJMlViUBEECVJ657fuSXEyKXfjIOy3jUJm76P6-BfsuWvyiUXuWnkUF-RKrMKHxdiPvE49vqMeco1m5SxTyW1TOQ7gPWllxCrndNzQt88aNfNhI_pGEd2V3zTpn5mHJ1QtUC04QjvdgbqDmS3P1FFJQBNxPUR7JVKyZykx10QO2p-2FESrMJpOHbAxlJBwCK9KErYYGfJtNarYjDcAgc-D4M5QqaffjJcd55M5Yw==';
        $b = CryptManager::decrypt($a);

        $asd = [
            [
                'table-url' => 'app91I09gFeMUscCG/Table 1',
                'list-id'   => 'someList',
                'view'      => NULL,
            ],
        ];

        $b['custom_form'] = $asd;
        $c                = CryptManager::encrypt($b);

        $systemInstal = $this->createMock(SystemInstallRepository::class);
        $systemInstal->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var PHPUnit_Framework_MockObject_MockObject|DocumentManager $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstal);

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlManagerInterface::class);
        $sender
            ->method('send')
            ->willReturn(new ResponseDto(200, '', $this->getResponseData(), []));

        return new AirtableCreateContactConnector($this->mockSystem(), $dm, $sender);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|AirtableSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('POST', new Uri('http://airtable.com/'));
        $requestDto->setHeaders([]);
        $mock = $this->createMock(AirtableSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

    /**
     * @return string
     */
    private function getResponseData(): string
    {
        return json_encode([
            'records' => [
                [
                    'fields' => [
                        'Name'  => 'abc',
                        'Email' => 'a@a.com',
                    ],
                ],
            ],
        ]);
    }

}