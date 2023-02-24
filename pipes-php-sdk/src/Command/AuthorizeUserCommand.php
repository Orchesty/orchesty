<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Command;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class AuthorizeUserCommand
 *
 * @package Hanaboso\PipesPhpSdk\Command
 */
final class AuthorizeUserCommand extends Command
{

    /**
     * AuthorizeUserCommand constructor.
     *
     * @param ApplicationManager $applicationManager
     */
    public function __construct(private ApplicationManager $applicationManager)
    {
        parent::__construct();
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
     * @return int
     * @throws ApplicationInstallException
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $env = $input->getParameterOption(['--env', '-e']);
        if ($env !== 'oauthconsole') {
            $output->writeln('<error>Please make sure that your env is set to --env=oauthconsole.</error>');

            return 1;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question1 = new Question(sprintf('Please input app key.%s', PHP_EOL));
        $key       = $helper->ask($input, $output, $question1);

        $question2 = new Question(sprintf('Please input user name.%s', PHP_EOL));
        $user      = $helper->ask($input, $output, $question2);

        if (!is_string($key) || !is_string($user)) {
            $output->writeln('Please make sure that input parameters are string.');

            return 1;
        }

        $output->writeln($this->applicationManager->authorizeApplication($key, $user, ''));

        return 0;
    }

}
