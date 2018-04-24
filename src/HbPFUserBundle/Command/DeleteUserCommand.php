<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\HbPFUserBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Enum\ResourceEnum;
use Hanaboso\PipesFramework\User\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\PipesFramework\User\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteUserCommand
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Command
 */
class DeleteUserCommand extends Command
{

    private const CMD_NAME = 'user:delete';

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var OrmRepo|OdmRepo
     */
    private $repo;

    /**
     * CreateUserCommand constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        ResourceProvider $provider
    )
    {
        parent::__construct();
        $this->dm   = $userDml->get();
        $this->repo = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Delete user.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $c = $this->repo->getUserCount();
        if ($c <= 1) {
            $output->writeln('Cannot delete when there is last one or none active users remaining.');
        } else {
            $output->writeln('Deleting user, select user email:');

            $email = readline();
            /** @var UserInterface $user */
            $user = $this->repo->findOneBy(['email' => $email]);

            if (!$user) {
                $output->writeln('User with given email doesn\'t exist.');
            } else {
                $this->dm->remove($user);
                $this->dm->flush();

                $output->writeln('User deleted.');
            }
        }
    }

}