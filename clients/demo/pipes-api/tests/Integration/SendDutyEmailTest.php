<?php declare(strict_types=1);

namespace Tests\Integration;

use Demo\CustomNode\SendDutyEmail;
use EmailServiceBundle\Mailer\Mailer;
use EmailServiceBundle\Transport\TransportInterface;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class SendDutyEmailTest
 *
 * @package Tests\Integration
 */
final class SendDutyEmailTest extends KernelTestCase
{

    /**
     * @throws Exception
     */
    public function testSend(): void
    {
        /** @var TransportInterface|MockObject $transport */
        $transport = $this->createPartialMock(TransportInterface::class, ['send', 'setLogger']);
        $transport->method('send')->willReturn(1);

        $pagerDuty = self::$container->get('hbpf.connector.pager-duty');
        $result    = new SendDutyEmail(new Mailer($transport), $pagerDuty);
        $dto       = (new ProcessDto())
            ->setData(Json::encode(['since' => '2019-04-19', 'until' => '2019-04-29']));

        self::assertNotEmpty($result->process($dto)->getData());
    }

    /**
     *
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

}
