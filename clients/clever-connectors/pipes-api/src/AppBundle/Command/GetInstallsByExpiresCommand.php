<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Command;

use CleverConnectors\AppBundle\Document\SystemInstall;
use DateTime;
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
class GetInstallsByExpiresCommand extends Command implements LoggerAwareInterface
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
     * @var int
     */
    private $interval;

    /**
     * GetSystemCommand constructor.
     *
     * @param DocumentManager $dm
     * @param int             $interval
     */
    public function __construct(DocumentManager $dm, int $interval)
    {
        parent::__construct('react:get-installs-by-expires');

        $this->dm       = $dm;
        $this->logger   = new NullLogger();
        $this->interval = $interval;
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
            // interval is in seconds
            $expires = time() + $this->interval * 2;

            // convert to datetime
            $datetime = new DateTime();
            $datetime->setTimestamp($expires);

            $systemInstalls = $this->dm->getRepository(SystemInstall::class)->findByExpires($datetime);

            $output->writeln(json_encode($systemInstalls));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return 1;
        }

        return 0;
    }

}