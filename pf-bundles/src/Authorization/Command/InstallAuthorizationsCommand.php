<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 9:00 AM
 */

namespace Hanaboso\PipesFramework\Authorization\Command;

use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Loader\AuthorizationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallAuthorizationsCommand
 *
 * @package Hanaboso\PipesFramework\Authorization\Command
 */
class InstallAuthorizationsCommand extends Command
{

    /**
     * @var AuthorizationLoader
     */
    private $loader;

    /**
     * InstallAuthorizationsCommand constructor.
     *
     * @param AuthorizationLoader $loader
     * @param null                $name
     */
    public function __construct(AuthorizationLoader $loader, $name = NULL)
    {
        parent::__construct($name);
        $this->loader = $loader;
    }

    /**
     *
     */
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('authorization.install')
            ->setDescription('Installs all authorizations into db.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->loader->installAllAuthorizations();
    }

}