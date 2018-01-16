<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Utils\Dto\Schema;
use Nette\Utils\Arrays;

/**
 * Class TopologySchemaUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
class TopologySchemaUtils
{

    private const PROCESS       = 'bpmn:process';
    private const START_EVENT   = 'bpmn:startEvent';
    private const TASK          = 'bpmn:task';
    private const EVENT         = 'bpmn:event';
    private const END_EVENT     = 'bpmn:endEvent';
    private const SEQUENCE_FLOW = 'bpmn:sequenceFlow';
    private const SOURCE_REF    = '@sourceRef';
    private const TARGET_REF    = '@targetRef';
    private const INCOMING      = 'bpmn:incoming';
    private const OUTGOING      = 'bpmn:outgoing';

    /** @var array */
    private static $handlers = [self::START_EVENT, self::TASK, self::EVENT, self::END_EVENT];

    /**
     * @param array $data
     *
     * @return Schema
     */
    public static function getSchemaObject(array $data): Schema
    {
        $schema = new Schema();

        if (isset($data[self::PROCESS])) {

            $processes = $data[self::PROCESS];
            unset($data);

            foreach ($processes as $handler => $process) {
                if (in_array($handler, self::$handlers)) {

                    if (!Arrays::isList($process)) {
                        $tmp = $process;
                        unset($process);
                        $process[0] = $tmp;
                    }

                    foreach ($process as $innerProcess) {

                        if (isset($innerProcess[self::OUTGOING]) && !isset($innerProcess[self::INCOMING])) {
                            $schema->setStartNode($innerProcess['@id']);
                        }

                        $schema->addNode($innerProcess['@id'], [
                            'handler'     => $handler,
                            'id'          => $innerProcess['@id'],
                            'name'        => $innerProcess['@name'] ?? '',
                            'cron_time'   => $innerProcess['@pipes:cronTime'] ?? '',
                            'cron_params' => $innerProcess['@pipes:cronParams'] ?? '',
                            'pipes_type'  => $innerProcess['@pipes:pipesType'] ?? '',
                        ]);
                    }
                }
            }

            if (isset($processes[self::SEQUENCE_FLOW])) {
                if (!isset($processes[self::SEQUENCE_FLOW][0])) {
                    $tmp = $processes[self::SEQUENCE_FLOW];
                    unset($processes[self::SEQUENCE_FLOW]);
                    $processes[self::SEQUENCE_FLOW][0] = $tmp;
                }

                foreach ($processes[self::SEQUENCE_FLOW] as $link) {
                    $schema->addSequence($link[self::SOURCE_REF], $link[self::TARGET_REF]);
                }
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
        return md5(json_encode($schema->buildIndex()));
    }

}