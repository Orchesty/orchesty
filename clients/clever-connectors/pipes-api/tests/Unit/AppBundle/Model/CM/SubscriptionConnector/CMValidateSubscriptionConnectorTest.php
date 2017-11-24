<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\SubscriptionConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CMValidateSubscriptionConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMValidateSubscriptionConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\SubscriptionConnector
 */
final class CMValidateSubscriptionConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers CMValidateSubscriptionConnector::processAction()
     */
    public function testCMConnectors(): void
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->will($this->returnCallback(function (RequestDto $requestDto) {
            $expt = new RequestDto('POST', new Uri('https://api.dev.clevermonitor.com/v1.2/validation/email'));
            $expt->setHeaders([
                'Accept'       => 'application/json',
                'Content-type' => 'application/json',
                'X-Api-Key'    => 'gguid:ttoken',
            ])->setBody('{"data":[]}');

            self::assertEquals($expt, $requestDto);

            return new ResponseDto(200, '', 'someBody', []);
        }));
        $conn = new CMValidateSubscriptionConnector($curl);

        $res = $conn->processAction((new ProcessDto())
            ->setData('{"data":[]}')
            ->setHeaders(
                [
                    CMHeaders::createKey(CMHeaders::TOKEN)      => 'ttoken',
                    CMHeaders::createKey(CMHeaders::GUID)       => 'gguid',
                    CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => 'system',
                ]
            ));
        self::assertEquals('someBody', $res->getData());
    }

    /**
     * @covers CMValidateSubscriptionConnector::processAction()
     */
    public function testCMConnectorsMissingGuid(): void
    {
        $conn = $this->container->get('hbpf.connector.cleverconnectors-validate-subscriptions-connector');
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction((new ProcessDto())->setHeaders(['token' => 'token']));
    }

    /**
     * @covers CMValidateSubscriptionConnector::processAction()
     */
    public function testCMConnectorsMissingToken(): void
    {
        $conn = $this->container->get('hbpf.connector.cleverconnectors-validate-subscriptions-connector');
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction((new ProcessDto())->setHeaders(['guis' => 'guid']));
    }

}