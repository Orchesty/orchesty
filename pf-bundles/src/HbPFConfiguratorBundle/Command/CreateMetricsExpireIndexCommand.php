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

    public const int EXPIRE_INDEX_DEFAULT = 2_592_000;

    public const string BUCKET = 'bucket';

    private const string NAME = 'metrics:expire:create';

    private const string FIELDS_CREATED = 'fields.created';

    private const array EXPIRE_INDEX_COLLECTIONS = [
        'connectors'                   => self::FIELDS_CREATED,
        'hourly_connectors_aggregates' => self::BUCKET,
        'hourly_pipes_node_aggregates' => self::BUCKET,
        'limiter'                      => self::FIELDS_CREATED,
        'monolith'                     => self::FIELDS_CREATED,
        'pipes_counter'                => self::FIELDS_CREATED,
        'pipes_node'                   => self::FIELDS_CREATED,
        'rabbitmq'                     => self::FIELDS_CREATED,
        'rabbitmq_consumer'            => self::FIELDS_CREATED,
        'repeater'                     => self::FIELDS_CREATED,
        'userTask'                     => self::FIELDS_CREATED,
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
     * @param string $collection
     * @param string $field
     *
     * @return string
     */
    public static function expireIndexName(string $collection, string $field): string
    {
        return sprintf('IK_%s_%s', $collection, lcfirst(str_replace('.', '', ucwords($field, '.'))));
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

        foreach (self::EXPIRE_INDEX_COLLECTIONS as $collection => $field) {
            $indexName = self::expireIndexName($collection, $field);

            try {
                $database
                    ->selectCollection($collection)
                    ->createIndex(
                        [$field => 1],
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
