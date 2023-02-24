<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Utils\Dto\NodeSchemaDto;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Hanaboso\Utils\Arrays\Arrays;
use Hanaboso\Utils\String\Json;

/**
 * Class TopologySchemaUtils
 *
 * @package Hanaboso\PipesFramework\Utils
 */
final class TopologySchemaUtils
{

    private const BPMN_PROCESS = 'bpmn:process';

    private const BPMN_START_EVENT       = 'bpmn:startEvent';
    private const BPMN_TASK              = 'bpmn:task';
    private const BPMN_EVENT             = 'bpmn:event';
    private const BPMN_END_EVENT         = 'bpmn:endEvent';
    private const BPMN_GATEWAY           = 'bpmn:gateway';
    private const BPMN_EXCLUSIVE_GATEWAY = 'bpmn:exclusiveGateway';

    private const BPMN_SEQUENCE_FLOW = 'bpmn:sequenceFlow';
    private const SOURCE_REF         = '@sourceRef';
    private const TARGET_REF         = '@targetRef';

    private const BPMN_INCOMING = 'bpmn:incoming';
    private const BPMN_OUTGOING = 'bpmn:outgoing';

    private const SDK_HOST          = '@pipes:sdkHost';
    private const BRIDGE_HOST       = '@pipes:bridgeHost';
    private const RABBIT_PREFETCH   = '@pipes:rabbitPrefetch';
    private const REPEATER_ENABLED  = '@pipes:repeaterEnabled';
    private const REPEATER_HOPS     = '@pipes:repeaterHops';
    private const REPEATER_INTERVAL = '@pipes:repeaterInterval';
    private const TIMEOUT           = '@pipes:timeout';

    /**
     * @var string[]
     */
    private static array $bpmnHandlers = [
        self::BPMN_START_EVENT,
        self::BPMN_TASK,
        self::BPMN_EVENT,
        self::BPMN_END_EVENT,
        self::BPMN_GATEWAY,
        self::BPMN_EXCLUSIVE_GATEWAY,
    ];

    /**
     * @param mixed[] $data
     *
     * @return Schema
     * @throws TopologyException
     */
    public static function getSchemaObject(array $data): Schema
    {
        $schema = new Schema();

        if (count($data) !== 0) {
            if (!isset($data[self::BPMN_PROCESS])) {
                throw new TopologyException('Unsupported schema!', TopologyException::UNSUPPORTED_SCHEMA);
            }
        } else {
            return $schema;
        }
        $processes    = $data[self::BPMN_PROCESS];
        $handlers     = self::$bpmnHandlers;
        $outgoing     = self::BPMN_OUTGOING;
        $incoming     = self::BPMN_INCOMING;
        $sequenceFlow = self::BPMN_SEQUENCE_FLOW;
        unset($data);

        foreach ($processes as $handler => $process) {
            if (in_array($handler, $handlers, TRUE)) {

                if (!Arrays::isList($process)) {
                    $process = [$process];
                }
                foreach ($process as $innerProcess) {

                    if (isset($innerProcess[$outgoing]) && !isset($innerProcess[$incoming])) {
                        $schema->addStartNode($innerProcess['@id']);
                    }

                    $type = $innerProcess['@pipes:pipesType'] ?? self::getPipesType($handler);

                    $topologyDto = new NodeSchemaDto(
                        $handler,
                        $innerProcess['@id'],
                        $type,
                        self::createConfigDto($innerProcess),
                        $innerProcess['@name'] ?? '',
                        $innerProcess['@pipes:cronTime'] ?? '',
                        $innerProcess['@pipes:cronParams'] ?? '',
                        $innerProcess['@pipes:appName'] ?? '',
                    );

                    $schema->addNode($innerProcess['@id'], $topologyDto);
                }
            }
        }

        if (isset($processes[$sequenceFlow])) {
            if (!isset($processes[$sequenceFlow][0])) {
                $tmp = $processes[$sequenceFlow];
                unset($processes[$sequenceFlow]);
                $processes[$sequenceFlow][0] = $tmp;
            }

            foreach ($processes[$sequenceFlow] as $link) {
                $schema->addSequence($link[self::SOURCE_REF], $link[self::TARGET_REF]);
            }
        }

        return $schema;
    }

    /**
     * @param Schema $schema
     * @param bool   $checkInfiniteLoop
     *
     * @return string
     * @throws TopologyException
     */
    public static function getIndexHash(Schema $schema, bool $checkInfiniteLoop = TRUE): string
    {
        return hash('sha256', Json::encode($schema->buildIndex($checkInfiniteLoop)));
    }

    /**
     * @param Schema $schema
     *
     * @return string
     */
    public static function getSchemaFullIndexHash(Schema $schema): string
    {
        $schemaIndex = [];
        foreach ($schema->getNodes() as $nodeKey => $nodeBody) {
            $schemaIndex[] = sprintf('schema_key_%s', $nodeKey);
            $schemaIndex[] = sprintf('schema_id_%s_%s', $nodeKey, $nodeBody->getId());
            $schemaIndex[] = sprintf('schema_name_%s_%s', $nodeKey, $nodeBody->getName());
            $schemaIndex[] = sprintf('schema_handler_%s_%s', $nodeKey, $nodeBody->getHandler());
            $schemaIndex[] = sprintf('schema_pipes_type_%s_%s', $nodeKey, $nodeBody->getPipesType());
            $schemaIndex[] = sprintf('schema_cron_time_%s_%s', $nodeKey, $nodeBody->getCronTime());
            $schemaIndex[] = sprintf('schema_cron_params_%s_%s', $nodeKey, $nodeBody->getCronParams());

            foreach ($nodeBody->getSystemConfigsArray() as $configKey => $configValue) {
                $schemaIndex[] = sprintf('schema_system_config_%s%s_%s', $nodeKey, $configKey, $configValue);
            }
        }
        foreach ($schema->getSequences() as $sequenceKey => $sequence) {
            foreach ($sequence as $sequenceValue) {
                $schemaIndex[] = sprintf('sequence_%s_%s', $sequenceKey, $sequenceValue);
            }
        }

        foreach ($schema->getStartNode() as $startNode) {
            $schemaIndex[] = sprintf('start_node_%s', $startNode);
        }

        sort($schemaIndex);

        return hash('sha256', Json::encode($schemaIndex));
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private static function getPipesType(string $type): string
    {
        return match ($type) {
            self::BPMN_GATEWAY, self::BPMN_EXCLUSIVE_GATEWAY => TypeEnum::GATEWAY->value,
            self::BPMN_EVENT, self::BPMN_START_EVENT => TypeEnum::START->value,
            self::BPMN_TASK => TypeEnum::CUSTOM->value,
            default => '',
        };
    }

    /**
     * @param mixed[] $data
     *
     * @return SystemConfigDto
     */
    private static function createConfigDto(array $data): SystemConfigDto
    {
        return new SystemConfigDto(
            $data[self::SDK_HOST] ?? '',
            $data[self::BRIDGE_HOST] ?? '',
            intval($data[self::RABBIT_PREFETCH] ?? 1),
            ($data[self::REPEATER_ENABLED] ?? 'false') === 'true',
            intval($data[self::REPEATER_HOPS] ?? 0),
            intval($data[self::REPEATER_INTERVAL] ?? 0),
            intval($data[self::TIMEOUT] ?? 60),
        );
    }

}
