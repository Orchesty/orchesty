<?php declare(strict_types=1);

namespace DemoTests\Integration;

use Demo\Connector\PagerDutyConnector;
use Demo\CustomNode\SendDutyEmail;
use DemoTests\KernelTestCaseAbstract;
use EmailServiceBundle\Mailer\Mailer;
use EmailServiceBundle\Transport\TransportInterface;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;

/**
 * Class SendDutyEmailTest
 *
 * @package DemoTests\Integration
 */
final class SendDutyEmailTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\SendDutyEmail
     * @covers \Demo\CustomNode\SendDutyEmail::processAction
     * @covers \Demo\CustomNode\SendDutyEmail::send
     * @covers \Demo\CustomNode\SendDutyEmail::getSubject
     *
     * @throws Exception
     */
    public function testSend(): void
    {
        $transport = $this->createPartialMock(TransportInterface::class, ['send', 'setLogger']);
        $transport->method('send');

        $curl = self::createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturn(
            new ResponseDto(
                200,
                '',
                File::getContent(__DIR__ . '/Connector/data/pagerDuty.json'),
                [],
            ),
        );

        $repo = self::getContainer()->get('hbpf.application_install.repository');

        $pagerDutyConnector = new PagerDutyConnector($repo);
        $pagerDutyConnector->setSender($curl);

        $repo   = self::getContainer()->get('hbpf.application_install.repository');
        $result = new SendDutyEmail($repo, new Mailer($transport), $pagerDutyConnector);
        $dto    = (new ProcessDto())
            ->setData(Json::encode(['since' => '2019-04-19', 'until' => '2019-04-29']));

        self::assertNotEmpty($result->processAction($dto)->getData());
    }

}
