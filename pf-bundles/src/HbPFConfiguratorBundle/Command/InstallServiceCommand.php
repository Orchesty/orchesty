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

    private const string NAME = 'name';
    private const string URL  = 'url';
    private const string TYPE = 'type';

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
            ->addArgument(self::TYPE, InputArgument::OPTIONAL, 'Type of service', Sdk::TYPE_HTTP)
            ->setDescription('Required arguments are: name and url. Optional: type (http|tunnel).')
            ->setHelp('Required arguments are: name and url. Optional: type (http|tunnel).');
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
        $type = $input->getArgument(self::TYPE);

        if (!in_array($type, [Sdk::TYPE_HTTP, Sdk::TYPE_TUNNEL], TRUE)) {
            $output->writeln(
                sprintf(
                    "Invalid type '%s'. Allowed values are: %s, %s.",
                    $type,
                    Sdk::TYPE_HTTP,
                    Sdk::TYPE_TUNNEL,
                ),
            );

            return 1;
        }

        $topologies = $this->manager->getAll();
        foreach ($topologies as $topology) {
            if ($topology->getName() === $name && $topology->getUrl() === $url && $topology->getType() === $type) {
                $output->writeln('Allready exists!');

                return 0;
            }
        }
        $this->manager->create([Sdk::NAME => $name, Sdk::URL => $url, Sdk::TYPE => $type]);
        $output->writeln('Done!');

        return 0;
    }

}
