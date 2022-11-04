<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Hanaboso\PipesFramework\Configurator\Model\ApiTokenManager;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateApiTokenCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
final class CreateApiTokenCommand extends Command
{

    private const USER      = 'user';
    private const EXPIRE_AT = 'expireAt';
    private const SCOPES    = 'scopes';

    /**
     * CreateApiTokenCommand constructor.
     *
     * @param ApiTokenManager $manager
     */
    public function __construct(private readonly ApiTokenManager $manager)
    {
        parent::__construct();
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName('api-token:create')
            ->addOption(self::EXPIRE_AT, 'ea', InputOption::VALUE_OPTIONAL, 'Expiration date')
            ->addOption(self::USER, 'u', InputOption::VALUE_OPTIONAL, 'User')
            ->addOption(self::SCOPES, 's', InputOption::VALUE_OPTIONAL, 'Scopes')
            ->setDescription('Create new ApiToken');
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
        $user     = $input->getOption(self::USER) ?? ApplicationController::SYSTEM_USER;
        $expireAt = $input->getOption(self::EXPIRE_AT);
        $scopes   = $input->getOption(self::SCOPES) ?? ApiTokenScopesEnum::getChoices();

        $data = [ApiToken::SCOPES => $scopes];
        if ($expireAt) {
            $data[ApiToken::EXPIRE_AT] = $expireAt;
        }

        $apiToken = $this->manager->create($data, $user);
        $output->writeln($apiToken->getKey());

        return 0;
    }

}
