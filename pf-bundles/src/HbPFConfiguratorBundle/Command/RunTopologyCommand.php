<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 9:00 AM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunTopologyCommand
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Command
 */
class RunTopologyCommand extends Command
{

    private const CMD_NAME = 'topology:run';

    /**
     * @var StartingPointHandler
     */
    private $handler;

    /**
     * RunTopologyCommand constructor.
     *
     * @param StartingPointHandler $handler
     * @param null                 $name
     */
    public function __construct(StartingPointHandler $handler, $name = NULL)
    {
        parent::__construct($name);
        $this->handler = $handler;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Runs the topology.')
            ->addArgument('topology_name', InputArgument::REQUIRED, 'topology_name')
            ->addArgument('node_name', InputArgument::REQUIRED, 'node_name')
            ->addArgument('param', InputArgument::OPTIONAL, 'param')
            ->setHelp('topology_run: [topology_id] [node_id] [param]');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->handler->run(
            $input->getOption('topology_name'),
            $input->getOption('node_name'),
            $input->getOption('param')
        );
    }

}