<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Utils\TopologySchemaUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class MigrateTopologyCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
final class MigrateTopologyCommand extends Command
{

    private const string CMD_NAME = 'topology:migrate';

    private const string BPMN_PROCESS       = 'bpmn:process';
    private const string BPMN_EVENT         = 'bpmn:event';
    private const string BPMN_START_EVENT   = 'bpmn:startEvent';
    private const string BPMN_END_EVENT     = 'bpmn:endEvent';
    private const string BPMN_TASK          = 'bpmn:task';
    private const string BPMN_GATEWAY       = 'bpmn:gateway';
    private const string BPMN_EXCL_GATEWAY  = 'bpmn:exclusiveGateway';
    private const string BPMN_SEQUENCE_FLOW = 'bpmn:sequenceFlow';
    private const string BPMN_DIAGRAM       = 'bpmndi:BPMNDiagram';

    private const array EVENT_HANDLERS = [
        self::BPMN_END_EVENT,
        self::BPMN_EVENT,
        self::BPMN_START_EVENT,
    ];

    private const array TASK_HANDLERS = [
        self::BPMN_TASK,
    ];

    private const array GATEWAY_HANDLERS = [
        self::BPMN_EXCL_GATEWAY,
        self::BPMN_GATEWAY,
    ];

    private const array PIPES_TYPE_LABEL_MAP = [
        'batch'     => 'Batch',
        'connector' => 'Connector',
        'cron'      => 'Cron',
        'custom'    => 'Custom Action',
        'start'     => 'Event',
        'webhook'   => 'Webhook',
    ];

    /**
     * MigrateTopologyCommand constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(private DocumentManager $dm)
    {
        parent::__construct(self::CMD_NAME);
    }

    /**
     * @param mixed[] $bpmn
     *
     * @return mixed[]
     */
    public static function convertBpmnToJson(array $bpmn): array
    {
        $connections = [];
        $idMap       = [];
        $nodes       = [];
        $positionMap = self::buildPositionMap($bpmn);

        if (!isset($bpmn[self::BPMN_PROCESS])) {
            return ['connections' => [], 'nodes' => []];
        }

        $process = $bpmn[self::BPMN_PROCESS];

        foreach (self::EVENT_HANDLERS as $handler) {
            if (!isset($process[$handler])) {
                continue;
            }

            foreach (self::normalizeToList($process[$handler]) as $event) {
                $oldId      = $event['@id'];
                $newId      = bin2hex(random_bytes(8));
                $pipesType  = $event['@pipes:pipesType'] ?? 'start';
                $isEndEvent = $handler === self::BPMN_END_EVENT;

                $idMap[$oldId] = $newId;

                $nodes[] = [
                    'action'   => NULL,
                    'id'       => $newId,
                    'label'    => $isEndEvent ? 'End Event' : self::resolveLabel($pipesType),
                    'name'     => $isEndEvent ? NULL : ($event['@name'] ?? NULL),
                    'position' => $positionMap[$oldId] ?? ['x' => 0, 'y' => 0],
                    'property' => NULL,
                    'shape'    => 'circle',
                    'type'     => 'flow',
                ];
            }
        }

        foreach (self::TASK_HANDLERS as $handler) {
            if (!isset($process[$handler])) {
                continue;
            }

            foreach (self::normalizeToList($process[$handler]) as $task) {
                $oldId     = $task['@id'];
                $newId     = bin2hex(random_bytes(8));
                $pipesType = $task['@pipes:pipesType'] ?? 'custom';
                $appName   = $task['@pipes:appName'] ?? '' ?: NULL;

                $idMap[$oldId] = $newId;

                $nodes[] = [
                    'action'   => [
                        'app'    => $appName,
                        'name'   => $task['@name'] ?? '',
                        'type'   => $pipesType,
                        'worker' => $task['@pipes:sdkHostName'] ?? '',
                    ],
                    'id'       => $newId,
                    'label'    => self::resolveLabel($pipesType),
                    'name'     => NULL,
                    'position' => $positionMap[$oldId] ?? ['x' => 0, 'y' => 0],
                    'property' => NULL,
                    'shape'    => 'square',
                    'type'     => 'flow',
                ];
            }
        }

        foreach (self::GATEWAY_HANDLERS as $handler) {
            if (!isset($process[$handler])) {
                continue;
            }

            foreach (self::normalizeToList($process[$handler]) as $gateway) {
                $oldId = $gateway['@id'];
                $newId = bin2hex(random_bytes(8));

                $idMap[$oldId] = $newId;

                $nodes[] = [
                    'action'   => NULL,
                    'id'       => $newId,
                    'label'    => 'Gateway',
                    'name'     => NULL,
                    'position' => $positionMap[$oldId] ?? ['x' => 0, 'y' => 0],
                    'property' => NULL,
                    'shape'    => 'square',
                    'type'     => 'flow',
                ];
            }
        }

        if (isset($process[self::BPMN_SEQUENCE_FLOW])) {
            foreach (self::normalizeToList($process[self::BPMN_SEQUENCE_FLOW]) as $flow) {
                $sourceOld = $flow['@sourceRef'] ?? '';
                $targetOld = $flow['@targetRef'] ?? '';

                if (!isset($idMap[$sourceOld], $idMap[$targetOld])) {
                    continue;
                }

                $connections[] = [
                    'from'         => $idMap[$sourceOld],
                    'id'           => bin2hex(random_bytes(8)),
                    'sourceOutput' => 'output',
                    'targetInput'  => 'input',
                    'to'           => $idMap[$targetOld],
                ];
            }
        }

        return ['connections' => $connections, 'nodes' => $nodes];
    }

    /**
     * @param mixed[] $bpmn
     *
     * @return mixed[]
     */
    public static function buildPositionMap(array $bpmn): array
    {
        $map = [];

        $plane = $bpmn[self::BPMN_DIAGRAM]['bpmndi:BPMNPlane']
            ?? $bpmn[self::BPMN_DIAGRAM][0]['bpmndi:BPMNPlane']
            ?? NULL;

        if ($plane === NULL || !isset($plane['bpmndi:BPMNShape'])) {
            return $map;
        }

        foreach (self::normalizeToList($plane['bpmndi:BPMNShape']) as $shape) {
            $elementId = $shape['@bpmnElement'] ?? '';
            $bounds    = $shape['dc:Bounds'] ?? [];

            if ($elementId && $bounds) {
                $map[$elementId] = [
                    'x' => (int) ((($bounds['@x'] ?? 0) + ($bounds['@width'] ?? 0)) * 1.22),
                    'y' => (int) ((($bounds['@y'] ?? 0) + ($bounds['@height'] ?? 0) / 2) * 1.45),
                ];
            }
        }

        return $map;
    }

    /**
     * @param string $pipesType
     *
     * @return string
     */
    public static function resolveLabel(string $pipesType): string
    {
        return self::PIPES_TYPE_LABEL_MAP[$pipesType] ?? ucfirst($pipesType);
    }

    /**
     * @param mixed $data
     *
     * @return mixed[]
     */
    public static function normalizeToList(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        if (!array_is_list($data)) {
            return [$data];
        }

        return $data;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Migrates old topology BPMN data to the new topology JSON data')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Re-migrate topologies that already have JSON data');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force    = $input->getOption('force');
        $migrated = 0;
        $skipped  = 0;
        $failed   = 0;

        /** @var Topology[] $topologies */
        $topologies = $this->dm->getRepository(Topology::class)->findAll();
        $total      = count($topologies);
        $sdkUrlMap  = [];

        foreach ($this->dm->getRepository(Sdk::class)->findAll() as $sdk) {
            $sdkUrlMap[$sdk->getName()] = $sdk->getUrl();
        }

        $output->writeln(sprintf(' Found %d topologies.', $total));

        foreach ($topologies as $topology) {
            $bpmn = $topology->getBpmn();

            if ($bpmn === []) {
                $output->writeln(
                    sprintf('  [SKIP] %s (v%d) - empty BPMN', $topology->getName(), $topology->getVersion()),
                );
                $skipped++;

                continue;
            }

            if (!$force && $topology->getJson() !== []) {
                $output->writeln(
                    sprintf('  [SKIP] %s (v%d) - already migrated', $topology->getName(), $topology->getVersion()),
                );
                $skipped++;

                continue;
            }

            try {
                $json         = self::convertBpmnToJson($bpmn);
                $schemaObject = TopologySchemaUtils::getSchemaObjectFromJson($json, $sdkUrlMap);
                $topology->setJson($json);
                $topology->setContentHash(TopologySchemaUtils::getIndexHash($schemaObject));
                $this->dm->persist($topology);
                $migrated++;
                $output->writeln(
                    sprintf('  [OK]   %s (v%d)', $topology->getName(), $topology->getVersion()),
                );
            } catch (Throwable $e) {
                $failed++;
                $output->writeln(
                    sprintf('  [FAIL] %s (v%d) - %s', $topology->getName(), $topology->getVersion(), $e->getMessage()),
                );
            }
        }

        $this->dm->flush();

        $output->writeln('');
        $output->writeln(sprintf(' Done. Migrated: %d, Skipped: %d, Failed: %d', $migrated, $skipped, $failed));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

}
