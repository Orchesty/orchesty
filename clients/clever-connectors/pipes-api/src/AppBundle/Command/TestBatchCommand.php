<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Command;

use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunTopologyCommand
 *
 * @package CleverConnectors\AppBundle\Command
 */
class TestBatchCommand extends Command
{

    private const CMD_NAME = 'test:publish-batch';

    /**
     * @var BunnyManager
     */
    private $bunnyManager;

    /**
     * RunTopologyCommand constructor.
     *
     * @param BunnyManager $bunnyManager
     */
    public function __construct(BunnyManager $bunnyManager)
    {
        parent::__construct(self::CMD_NAME);
        $this->bunnyManager = $bunnyManager;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Publish');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $channel = $this->bunnyManager->getChannel();

        $channel->publish('{"count": 100000}', [
            'reply-to'          => 'output',
            'type'              => 'batch',
            'pf-node-name'      => 'cleverconnectors-benchmark-batch-generator',
            'pf-node-id'        => '#123',
            'pf-correlation-id' => '#123',
            'pf-topology-id'    => '#123',
            'pf-process-id'     => '#123',
            'pf-parent-id'      => '#123',
        ], '', 'pipes.batch');
    }

}