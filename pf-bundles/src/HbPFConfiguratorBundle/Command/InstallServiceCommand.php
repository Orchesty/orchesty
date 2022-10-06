<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallServiceCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
final class InstallServiceCommand extends Command
{

    private const NAME = 'name';
    private const URL  = 'url';

    /**
     * InstallServiceCommand constructor.
     *
     * @param SdkManager $manager
     */
    public function __construct(private SdkManager $manager)
    {
        parent::__construct();
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName('service:install')
            ->addArgument(self::NAME, InputArgument::REQUIRED, 'Name of service')
            ->addArgument(self::URL, InputArgument::REQUIRED, 'Url of service')
            ->setDescription('Required arguments are: name and url.')
            ->setHelp('Required arguments are: name and url.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $name = $input->getArgument(self::NAME);
        $url  = $input->getArgument(self::URL);

        $topologies = $this->manager->getAll();
        foreach ($topologies as $topology) {
            if ($topology->getName() === $name && $topology->getUrl() === $url) {
                $output->writeln('Allready exists!');

                return 0;
            }
        }
        $this->manager->create([Sdk::NAME => $name, Sdk::URL => $url]);
        $output->writeln('Done!');

        return 0;
    }

}
