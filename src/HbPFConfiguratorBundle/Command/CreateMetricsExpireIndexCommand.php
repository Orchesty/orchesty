<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Metrics\Document\ProcessesMetrics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class CreateMetricsExpireIndexCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
class CreateMetricsExpireIndexCommand extends Command
{

    private const string NAME = 'metrics:expire:create';

    private const int EXPIRE_INDEX_DEFAULT = 2_592_000;

    private const array EXPIRE_INDEX_KEY = ['fields.created' => 1];

    private const array EXPIRE_INDEX_COLLECTIONS = [
        'connectors',
        'limiter',
        'monolith',
        'pipes_counter',
        'pipes_node',
        'rabbitmq',
        'rabbitmq_consumer',
        'repeater',
        'userTask',
    ];

    /**
     * CreateMetricsExpireIndexCommand constructor.
     *
     * @param DocumentManager $metricsDm
     */
    public function __construct(private readonly DocumentManager $metricsDm)
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(self::NAME);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;

        $expireAfterSeconds = $this->expireAfterSeconds();

        $output->writeln(sprintf('Setting metrics expire indexes to %d seconds', $expireAfterSeconds));

        $database = $this->metricsDm->getDocumentDatabase(ProcessesMetrics::class);

        foreach (self::EXPIRE_INDEX_COLLECTIONS as $collection) {
            $indexName = sprintf('IK_%s_fieldsCreated', $collection);

            try {
                $database
                    ->selectCollection($collection)
                    ->createIndex(
                        self::EXPIRE_INDEX_KEY,
                        [
                            'expireAfterSeconds' => $expireAfterSeconds,
                            'name'               => $indexName,
                        ],
                    );

                $output->writeln(sprintf(' DONE %s.%s: %d s (created)', $collection, $indexName, $expireAfterSeconds));
            } catch (Throwable) {
                try {
                    $database->command([
                        'collMod' => $collection,
                        'index'   => [
                            'expireAfterSeconds' => $expireAfterSeconds,
                            'name'               => $indexName,
                        ],
                    ]);

                    $output->writeln(
                        sprintf(' DONE %s.%s: %d s (updated)', $collection, $indexName, $expireAfterSeconds),
                    );
                } catch (Throwable $e) {
                    $output->writeln(sprintf(' SKIP %s.%s (%s)', $collection, $indexName, $e->getMessage()));
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return int
     */
    protected function expireAfterSeconds(): int
    {
        return self::EXPIRE_INDEX_DEFAULT;
    }

}
