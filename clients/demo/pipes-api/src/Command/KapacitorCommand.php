<?php declare(strict_types=1);

namespace Demo\Command;

use Hanaboso\CommonsBundle\Enum\MetricsEnum;
use Hanaboso\CommonsBundle\Metrics\Impl\InfluxDbSender;
use Hanaboso\CommonsBundle\Transport\Udp\UDPSender;
use Hanaboso\Utils\Exception\DateTimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class KapacitorCommand
 *
 * @package Demo\Command
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
     * @return int
     * @throws DateTimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;
        $output->writeln('Kapacitor start.');

        $sender = new InfluxDbSender(new UDPSender(), 'test', 'measurement');

        $i = 0;
        while ($i < 10_000) {
            $sender->send(
                [
                    MetricsEnum::REQUEST_TOTAL_DURATION => 123,
                    MetricsEnum::CPU_USER_TIME          => 0,
                    MetricsEnum::CPU_KERNEL_TIME        => 99,
                ],
                [
                    MetricsEnum::HOST           => gethostname(),
                    MetricsEnum::URI            => 'http://localhost.com',
                    MetricsEnum::TOPOLOGY_ID    => '#999',
                    MetricsEnum::CORRELATION_ID => '#456',
                    MetricsEnum::NODE_ID        => '#123',
                ]
            );
            usleep(1_000);
            $i++;
        }

        return 0;
    }

}
