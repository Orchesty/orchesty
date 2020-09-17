<?php declare(strict_types=1);

namespace DemoTests\Integration;

use Demo\CustomNode\SendDutyEmail;
use DemoTests\KernelTestCaseAbstract;
use EmailServiceBundle\Mailer\Mailer;
use EmailServiceBundle\Transport\TransportInterface;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
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
     * @covers \Demo\CustomNode\SendDutyEmail::process
     * @covers \Demo\CustomNode\SendDutyEmail::send
     * @covers \Demo\CustomNode\SendDutyEmail::getSubject
     *
     * @throws Exception
     */
    public function testSend(): void
    {
        $transport = $this->createPartialMock(TransportInterface::class, ['send', 'setLogger']);
        $transport->method('send')->willReturn(1);

        $pagerDuty = self::$container->get('hbpf.connector.pager-duty');
        $result    = new SendDutyEmail(new Mailer($transport), $pagerDuty);
        $dto       = (new ProcessDto())
            ->setData(Json::encode(['since' => '2019-04-19', 'until' => '2019-04-29']));

        self::assertNotEmpty($result->process($dto)->getData());
    }

}
