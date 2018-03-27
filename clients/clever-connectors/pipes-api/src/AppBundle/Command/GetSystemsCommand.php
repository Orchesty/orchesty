<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/2/17
 * Time: 1:11 PM
 */

namespace CleverConnectors\AppBundle\Command;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetSystemCommand
 *
 * @package CleverConnectors\AppBundle\Command
 */
class GetSystemsCommand extends Command implements LoggerAwareInterface
{

    private const SYSTEM = 'system-key';
    private const USER   = 'user';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetSystemCommand constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        parent::__construct('react:get-system');
        $this->dm     = $dm;
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this->addArgument(self::SYSTEM, InputArgument::REQUIRED, 'System key');
        $this->addArgument(self::USER, InputArgument::OPTIONAL, 'User GUID');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $cursor = $this->dm->getDocumentCollection(SystemInstall::class)->find($this->getConditions($input));

            $output->writeln(json_encode($cursor->toArray()));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return 1;
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return array
     */
    protected function getConditions(InputInterface $input): array
    {
        $cond = [SystemInstall::SYSTEM => $input->getArgument(self::SYSTEM)];

        if ($input->hasArgument(self::USER) && !empty($input->getArgument(self::USER))) {
            $cond[SystemInstall::USER] = $input->getArgument(self::USER);
        }

        return $cond;
    }

}