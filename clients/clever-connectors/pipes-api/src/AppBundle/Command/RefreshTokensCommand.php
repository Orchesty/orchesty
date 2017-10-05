<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Command;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RefreshTokensCommand
 *
 * @package CleverConnectors\AppBundle\Command
 */
class RefreshTokensCommand extends Command implements LoggerAwareInterface
{

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
        parent::__construct('tokens:refresh');

        $this->dm     = $dm;
        $this->logger = new NullLogger();
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
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
            // TODO expires

            $cursor = $this->dm->getDocumentCollection(SystemInstall::class)->find([
                'expires' => $expires,
            ]);

            $output->writeln(json_encode($cursor->toArray()));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return 1;
        }

        return 0;
    }

}