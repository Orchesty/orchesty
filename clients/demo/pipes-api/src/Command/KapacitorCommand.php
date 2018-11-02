<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/14/18
 * Time: 10:26 AM
 */

namespace Demo\Command;

use Hanaboso\CommonsBundle\Enum\MetricsEnum;
use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Metrics\UDPSender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DownloaderCommand
 *
 * @package App\Command
 */
class KapacitorCommand extends Command
{

    /**
     *
     */
    protected function configure(): void
    {
        $this->setName('kapacitor:run');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $input;
        $output->writeln('Kapacitor start.');

        $sender = new InfluxDbSender(new UDPSender('kapacitor', 9100), 'test');

        while (TRUE) {
            $sender->send(
                [
                    MetricsEnum::REQUEST_TOTAL_DURATION => 123,
                    MetricsEnum::CPU_USER_TIME          => 0,
                    MetricsEnum::CPU_KERNEL_TIME        => 99,
                ],
                [
                    MetricsEnum::HOST           => gethostname(),
                    MetricsEnum::URI            => "http://localhost.com",
                    MetricsEnum::TOPOLOGY_ID    => "#999",
                    MetricsEnum::CORRELATION_ID => "#456",
                    MetricsEnum::NODE_ID        => "#123",
                ]
            );
            usleep(1000);
        }
    }

}