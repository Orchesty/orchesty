<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/14/18
 * Time: 10:26 AM
 */

namespace Demo\Command;

use Hanaboso\PipesFramework\Commons\Enum\MetricsEnum;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Metrics\UDPSender;
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
     * DownloaderCommand constructor.
     */
    public function __construct()
    {
        parent::__construct('kapacitor:run');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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