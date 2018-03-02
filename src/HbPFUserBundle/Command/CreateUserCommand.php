<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\HbPFUserBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Enum\ResourceEnum;
use Hanaboso\PipesFramework\User\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\PipesFramework\User\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class CreateUserCommand
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Command
 */
class CreateUserCommand extends Command
{

    private const CMD_NAME = 'user:create';

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var OrmRepo|OdmRepo
     */
    private $repo;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * ChangePasswordCommand constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     * @param EncoderFactory         $encoderFactory
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        ResourceProvider $provider,
        EncoderFactory $encoderFactory
    )
    {
        parent::__construct();
        $this->dm       = $userDml->get();
        $this->repo     = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
        $this->encoder  = $encoderFactory->getEncoder($provider->getResource(ResourceEnum::USER));
        $this->provider = $provider;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Create new user password.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Creating user, select user email:');

        $pwd1  = '';
        $email = readline();
        /** @var UserInterface $user */
        $user = $this->repo->findOneBy(['email' => $email]);

        if ($user) {
            $output->writeln('User with given email exist.');
        } else {
            $userNamespace = $this->provider->getResource(ResourceEnum::USER);

            $user = new $userNamespace();
            $user->setEmail($email);
            $this->dm->persist($user);
            while (TRUE) {
                $output->writeln('Set new password:');
                system('stty -echo');
                $pwd1 = trim(fgets(STDIN));
                $output->writeln('Repeat password:');
                $pwd2 = trim(fgets(STDIN));
                system('stty echo');

                if ($pwd1 === $pwd2) {
                    break;
                }
                $output->writeln('Passwords don\'t match.');
            }
            $user->setPassword($this->encoder->encodePassword($pwd1, ''));
            $this->dm->flush($user);

            $output->writeln('User created.');
        }
    }

}