<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 10:56 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator;

use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Nette\Utils\Strings;

/**
 * Class GeneratorUtils
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
class GeneratorUtils
{

    /**
     * @param string $id
     * @param string $name
     *
     * @return string
     */
    public static function normalizeName(string $id, string $name): string
    {
        return sprintf('%s-%s', $id, Strings::webalize($name));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function denormalizeName(string $name): string
    {
        return preg_replace('/-.*$/', '', $name);
    }

    /**
     * @param string $id
     * @param string $name
     *
     * @return string
     */
    public static function dokerizeName(string $id, string $name): string
    {
        return strtolower(sprintf('%s%s', $id, preg_replace('/-|\s/', '', $name)));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function createServiceName(string $name): string
    {
        $pieces = [];
        $i      = 0;
        foreach (explode('-', $name) as $item) {
            if ($i === 0) {
                $pieces[] = $item;
            } else {
                $pieces[] = substr($item, 0, 3);
            }
            $i++;
        };

        return substr(implode('-', $pieces), 0, 63);
    }

    /**
     * @param string $id
     * @param string $name
     *
     * @return string
     */
    public static function createNormalizedServiceName(string $id, string $name): string
    {
        return self::createServiceName(self::normalizeName($id, $name));
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @return string
     */
    public static function generateQueueName(Topology $topology, Node $node): string
    {
        return sprintf(
            'pipes.%s.%s',
            self::createNormalizedServiceName($topology->getId(), $topology->getName()),
            self::createNormalizedServiceName($node->getId(), $node->getName())
        );
    }

    /**
     * @param string $topology
     * @param string $nodeId
     * @param string $nodeName
     *
     * @return string
     */
    public static function generateQueueNameFromStrings(string $topology, string $nodeId, string $nodeName): string
    {
        return sprintf(
            'pipes.%s.%s',
            $topology,
            self::createNormalizedServiceName($nodeId, $nodeName)
        );
    }

}
