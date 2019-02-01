<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Nette\Utils\Arrays;

/**
 * Class TopologySchemaUtils
 *
 * @package Hanaboso\PipesFramework\Utils
 */
class TopologySchemaUtils
{

    private const BPMN_PROCESS = 'bpmn:process';
    private const PROCESS      = 'process';

    private const BPMN_START_EVENT       = 'bpmn:startEvent';
    private const START_EVENT            = 'startEvent';
    private const BPMN_TASK              = 'bpmn:task';
    private const TASK                   = 'task';
    private const BPMN_EVENT             = 'bpmn:event';
    private const EVENT                  = 'event';
    private const BPMN_END_EVENT         = 'bpmn:endEvent';
    private const END_EVENT              = 'endEvent';
    private const BPMN_GATEWAY           = 'bpmn:gateway';
    private const GATEWAY                = 'gateway';
    private const BPMN_EXCLUSIVE_GATEWAY = 'bpmn:exclusiveGateway';
    private const EXCLUSIVE_GATEWAY      = 'exclusiveGateway';

    private const BPMN_SEQUENCE_FLOW = 'bpmn:sequenceFlow';
    private const SEQUENCE_FLOW      = 'sequenceFlow';
    private const SOURCE_REF         = '@sourceRef';
    private const TARGET_REF         = '@targetRef';

    private const BPMN_INCOMING = 'bpmn:incoming';
    private const INCOMING      = 'incoming';
    private const BPMN_OUTGOING = 'bpmn:outgoing';
    private const OUTGOING      = 'outgoing';

    /**
     * @var array
     */
    private static $bpmnHandlers = [
        self::BPMN_START_EVENT,
        self::BPMN_TASK,
        self::BPMN_EVENT,
        self::BPMN_END_EVENT,
        self::BPMN_GATEWAY,
        self::BPMN_EXCLUSIVE_GATEWAY,
    ];

    /**
     * @var array
     */
    private static $handlers = [
        self::START_EVENT, self::TASK, self::EVENT, self::END_EVENT, self::GATEWAY, self::EXCLUSIVE_GATEWAY,
    ];

    /**
     * @param array $data
     *
     * @return Schema
     */
    public static function getSchemaObject(array $data): Schema
    {
        $schema = new Schema();

        if (isset($data[self::PROCESS])) {
            $processes    = $data[self::PROCESS];
            $handlers     = self::$handlers;
            $outgoing     = self::OUTGOING;
            $incoming     = self::INCOMING;
            $sequenceFlow = self::SEQUENCE_FLOW;
        } elseif (isset($data[self::BPMN_PROCESS])) {
            $processes    = $data[self::BPMN_PROCESS];
            $handlers     = self::$bpmnHandlers;
            $outgoing     = self::BPMN_OUTGOING;
            $incoming     = self::BPMN_INCOMING;
            $sequenceFlow = self::BPMN_SEQUENCE_FLOW;
        } else {
            return $schema;
        }
        unset($data);

        foreach ($processes as $handler => $process) {
            if (in_array($handler, $handlers)) {

                if (!Arrays::isList($process)) {
                    $tmp = $process;
                    unset($process);
                    $process[0] = $tmp;
                }

                foreach ($process as $innerProcess) {

                    if (isset($innerProcess[$outgoing]) && !isset($innerProcess[$incoming])) {
                        $schema->setStartNode($innerProcess['@id']);
                    }

                    $type = $innerProcess['@pipes:pipesType'] ?? '';
                    if (!$type) {
                        $type = self::getPipesType($handler);
                    }

                    $schema->addNode($innerProcess['@id'], [
                        'handler'     => $handler,
                        'id'          => $innerProcess['@id'],
                        'name'        => $innerProcess['@name'] ?? '',
                        'cron_time'   => $innerProcess['@pipes:cronTime'] ?? '',
                        'cron_params' => $innerProcess['@pipes:cronParams'] ?? '',
                        'pipes_type'  => $type,
                    ]);
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
     *
     * @return string
     * @throws TopologyException
     */
    public static function getIndexHash(Schema $schema): string
    {
        return md5((string) json_encode($schema->buildIndex()));
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private static function getPipesType(string $type): string
    {
        switch ($type) {
            case in_array(
                $type,
                [self::GATEWAY, self::EXCLUSIVE_GATEWAY, self::BPMN_GATEWAY, self::BPMN_EXCLUSIVE_GATEWAY]
            ):
                return TypeEnum::GATEWAY;
            case in_array($type, [self::BPMN_EVENT, self::BPMN_START_EVENT, self::EVENT, self::START_EVENT]):
                return TypeEnum::START;
            case in_array($type, [self::BPMN_TASK, self::TASK]):
                return TypeEnum::CUSTOM;
            default:
                return '';
        }

    }

}
