<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Command;

use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Manager\ApplicationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class AuthorizeUserCommand
 *
 * @package Hanaboso\PipesPhpSdk\Command
 */
class AuthorizeUserCommand extends Command
{

    /**
     * @var ApplicationManager
     */
    private $applicationManager;

    /**
     * AuthorizeUserCommand constructor.
     *
     * @param ApplicationManager $applicationManager
     */
    public function __construct(ApplicationManager $applicationManager)
    {
        parent::__construct();
        $this->applicationManager = $applicationManager;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName('user:authorize')
            ->setDescription('Authorize user and get redirect url.')
            ->setHelp('In order to work properly set env to --env=oauthconsole.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws ApplicationInstallException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $env = $input->getParameterOption(['--env', '-e']);
        if ($env !== 'oauthconsole') {
            $output->writeln(sprintf('<error>Please make sure that your env is set to --env=oauthconsole.</error>'));

            return NULL;
        }

        $helper = $this->getHelper('question');

        $question1 = new Question(sprintf('Please input app key.%s', PHP_EOL));
        $key       = $helper->ask($input, $output, $question1);

        $question2 = new Question(sprintf('Please input user name.%s', PHP_EOL));
        $user      = $helper->ask($input, $output, $question2);

        if (!is_string($key) || !is_string($user)) {
            $output->writeln(sprintf('Please make sure that input parameters are string.'));

            return NULL;
        }

        $this->applicationManager->authorizeApplication($key, $user, '');

        return 1;
    }

}