<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMValidateSubscriberConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMValidateSubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\SubscriberConnector
 */
final class CMValidateSubscriberConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers CMValidateSubscriberConnector::processAction()
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
        $conn = new CMValidateSubscriberConnector($curl);

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
     * @covers CMValidateSubscriberConnector::processAction()
     */
    public function testCMConnectorsMissingGuid(): void
    {
        $conn = $this->ownContainer->get('hbpf.connector.cleverconnectors-validate-subscriptions-connector');
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction((new ProcessDto())->setHeaders(['token' => 'token']));
    }

    /**
     * @covers CMValidateSubscriberConnector::processAction()
     */
    public function testCMConnectorsMissingToken(): void
    {
        $conn = $this->ownContainer->get('hbpf.connector.cleverconnectors-validate-subscriptions-connector');
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction((new ProcessDto())->setHeaders(['guis' => 'guid']));
    }

}