<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\ListConnector;

use CleverConnectors\AppBundle\Model\CM\ListConnector\CMCreateDistributionListConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class CMCreateDistributionListConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\ListConnector
 */
final class CMCreateDistributionListConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetDistributionArray(): void
    {
        $conn = new CMCreateDistributionListConnector($this->mockCurl());

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::TOKEN) => 'token',
            CMHeaders::createKey(CMHeaders::GUID)  => 'guid',
        ]);
        $dto->setData(json_encode(['name' => 'Distribution Group', 'note' => 'abc']));

        $res = $conn->processAction($dto);
        $this->checkData(json_decode($res->getData(), TRUE));

        $dto->setData(json_encode(['name' => 'Distribution Group', 'note' => 'abc']));
        $res = $conn->createList($dto);
        $this->checkData($res);
    }

    /**
     * @param array $res
     */
    private function checkData(array $res): void
    {
        $list1 = [
            'name' => 'Distribution Group',
            'id'   => '70639670-b5d6-443c-c56a-7d93593e2c95',
        ];

        self::assertEquals($list1, $res);
    }

    /**
     * @return CurlManagerInterface|MockObject
     * @throws Exception
     */
    private function mockCurl()
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $expt = new RequestDto('POST',
                        new Uri('https://api.dev.clevermonitor.com/v1.2/lists'));
                    $expt->setHeaders([
                        'Accept'       => 'application/json',
                        'Content-type' => 'application/json; charset=utf-8',
                        'X-Api-Key'    => 'guid:token',
                    ]);
                    $expt->setBody(json_encode(['name' => 'Distribution Group', 'note' => 'abc']));
                    self::assertEquals($expt, $requestDto);

                    return new ResponseDto(201, '', $this->getRequest('created.json'), []);
                }
            ));

        return $curl;
    }

}