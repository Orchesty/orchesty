<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\ListConnector;

use CleverConnectors\AppBundle\Model\CM\ListConnector\CMGetDistributionsConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class CMGetDistributionsConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\ListConnector
 */
class CMGetDistributionsConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testGetDistributionArray(): void
    {
        $conn = new CMGetDistributionsConnector($this->mockCurl());

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::TOKEN) => 'token',
            CMHeaders::createKey(CMHeaders::GUID)  => 'guid',
        ]);

        $res = $conn->processAction($dto);
        $this->checkData(json_decode($res->getData(), TRUE));

        $res = $conn->getDistributionsArray($dto);
        $this->checkData($res);
    }

    /**
     * @param array $res
     */
    private function checkData(array $res): void
    {
        $list1 = [
            'list_id'     => '5ff42ca6-1965-49ed-97d0-b2b568c88bfd',
            'name'        => 'Name 1',
            'note'        => 'Description 1',
            'subscribers' => 30001,
            'date_from'   => '2016-01-07T00:00:00+01:00',
            'parent_id'   => NULL,
        ];
        $list2 = [
            'list_id'     => 'c2d73a4d-b66b-17c3-801e-8769f22e1fc2',
            'name'        => 'Name 2',
            'note'        => 'Description 2',
            'subscribers' => 0,
            'date_from'   => '2016-01-23T00:00:00+01:00',
            'parent_id'   => NULL,
        ];

        self::assertEquals($list1, $res[0]);
        self::assertEquals($list2, $res[1]);
    }

    /**
     * @return CurlManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $expt = new RequestDto('GET',
                        new Uri('https://api.dev.clevermonitor.com/v1.2/lists?count=50&offset=0'));
                    $expt->setHeaders([
                        'Accept'       => 'application/json',
                        'Content-type' => 'application/json',
                        'X-Api-Key'    => 'guid:token',
                    ]);
                    self::assertEquals($expt, $requestDto);

                    return new ResponseDto(200, '', $this->getRequest('distributions.json'), []);
                }
            ));

        return $curl;
    }

}