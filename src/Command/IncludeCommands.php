<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application as BundleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IncludeCommands
 *
 * @package Hanaboso\PipesFramework\Command
 */
class IncludeCommands extends BundleApplication
{

    /**
     * @var mixed[]
     */
    protected $defaultCommands = [
        'user:create',
        'user:delete',
        'user:list',
        'user:password:change',
        'rabbit_mq:async-consumer',
        'rabbit_mq:consumer',
        'rabbit_mq:setup',
        'rabbit_mq:publisher:pipes.messages',
        'authorization:install',
        'cron:refresh',
    ];

    /**
     * @var mixed[]
     */
    protected $includedCommands = [];

    /**
     * @param string $name
     *
     * @return IncludeCommands
     */
    public function addIncludedCommand(string $name): self
    {
        $this->includedCommands[] = $name;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getIncludedCommands(): array
    {
        $return = [];
        // help and list commands are added before container instantiation
        if ($this->getKernel()->getContainer() instanceof ContainerInterface) {
            $return = array_merge(
                $this->defaultCommands,
                $this->includedCommands
            );
        }

        return $return;
    }

    /**
     * @param Command $command
     *
     * @return Command|null
     */
    public function add(Command $command): ?Command
    {
        return parent::add($command->setHidden(!in_array($command->getName(), $this->getIncludedCommands(), TRUE)));
    }

}
