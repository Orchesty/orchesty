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
use Nette\Neon\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetSystemCommand
 *
 * @package CleverConnectors\AppBundle\Command
 */
class GetSystemCommand extends Command
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * GetSystemCommand constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        parent::__construct('react:get-system');
        $this->dm = $dm;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this->addArgument('system-key', InputArgument::REQUIRED, 'System key');
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
            $cursor = $this->dm->getDocumentCollection(SystemInstall::class)->find([
                'system' => $input->getArgument('system-key'),
            ]);

            foreach ($cursor as $item) {
                $output->writeln(json_encode($item));
            }
        } catch (Exception $e) {
            // @todo add no stdout logger
            return 1;
        }

        return 0;
    }

}