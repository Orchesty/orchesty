<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\CustomFieldsConnector;

use CleverConnectors\AppBundle\Model\CM\CustomFieldsConnector\CMGetCustomFieldsConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class CMGetCustomFieldsConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\CustomFieldsConnector
 */
final class CMGetCustomFieldsConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetDistributionArray(): void
    {
        $conn = new CMGetCustomFieldsConnector($this->mockCurl());

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::TOKEN) => 'token',
            CMHeaders::createKey(CMHeaders::GUID)  => 'guid',
        ]);

        $res = $conn->processAction($dto);
        $this->checkData(json_decode($res->getData(), TRUE));

        $res = $conn->getCustomFieldsArray($dto);
        $this->checkData($res);
    }

    /**
     * @param array $res
     */
    private function checkData(array $res): void
    {
        $list1 = [
            'field_id' => '852ebeeb-8f5f-8766-32f8-696d943051fb',
            'name'     => 'Sedadlo',
        ];
        $list2 = [
            'field_id' => 'f14b4906-4455-b045-3050-485071d0159d',
            'name'     => 'Hokejka',
        ];

        self::assertEquals($list1, $res[0]);
        self::assertEquals($list2, $res[1]);
    }

    /**
     * @return CurlManagerInterface|MockObject
     *
     * @throws Exception
     */
    private function mockCurl()
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $expt = new RequestDto('GET',
                        new Uri('https://api.dev.clevermonitor.com/v1.2/fields?source=1&count=50&offset=0'));
                    $expt->setHeaders([
                        'Accept'       => 'application/json',
                        'Content-type' => 'application/json',
                        'X-Api-Key'    => 'guid:token',
                    ]);
                    self::assertEquals($expt, $requestDto);

                    return new ResponseDto(200, '', $this->getRequest('fields.json'), []);
                }
            ));

        return $curl;
    }

}