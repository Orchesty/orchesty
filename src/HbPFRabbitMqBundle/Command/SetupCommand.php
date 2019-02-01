<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFRabbitMqBundle\Command;

use Exception;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetupCommand
 *
 * @package Hanaboso\PipesFramework\HbPFRabbitMqBundle\Command
 */
class SetupCommand extends Command
{

    /**
     * @var BunnyManager
     */
    public $manager;

    /**
     * SetupCommand constructor.
     *
     * @param BunnyManager $manager
     */
    public function __construct(BunnyManager $manager)
    {
        parent::__construct("rabbit-mq:setup");
        $this->manager = $manager;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription("Sets up exchange-queue topology as specified on RabbitMqBundle configuration.");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;
        $output;

        $this->manager->setUp();

        return 0;
    }

}
