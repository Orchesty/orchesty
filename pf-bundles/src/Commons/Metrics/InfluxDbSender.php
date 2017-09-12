<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 8/9/17
 * Time: 9:58 AM
 */

namespace Hanaboso\PipesFramework\Commons\Metrics;

use InvalidArgumentException;

/**
 * Class UDPService
 *
 * @package Hanaboso\PipesFramework\Commons\Metrics
 */
class InfluxDbSender
{

    /**
     * @var UDPSender
     */
    private $sender;

    /**
     * @var string
     */
    private $measurement;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * UDPService constructor.
     *
     * @param UDPSender $sender
     * @param string    $measurement
     * @param array     $tags
     */
    public function __construct(UDPSender $sender, string $measurement, array $tags = [])
    {
        $this->sender      = $sender;
        $this->measurement = $measurement;
        $this->tags        = $tags;
    }

    /**
     * @param array $fields
     *
     * @return bool
     */
    public function send(array $fields): bool
    {
        return $this->sender->send($this->createMessage($fields));
    }

    /**
     * @param array $fields
     *
     * @return string
     */
    public function createMessage(array $fields): string
    {
        if (empty($fields)) {
            throw new InvalidArgumentException('The fields must not be empty.');
        }

        $nanoTimestamp = (round(microtime(TRUE) * 1000)) . '000000';

        $this->tags['host'] = gethostname();

        return sprintf(
            '%s,%s %s %s',
            $this->measurement,
            $this->join($this->prepareTags($this->tags)),
            $this->join($this->prepareFields($fields)),
            $nanoTimestamp
        );
    }

    /**
     * @param array $items
     *
     * @return string
     */
    private function join(array $items): string
    {
        $result = '';
        foreach ($items as $key => $value) {

            $result .= sprintf('%s=%s,', $key, $value);

            if (!next($items)) {
                $result = substr($result, 0, -1);
            }
        }

        return $result;
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    private function prepareTags(array $tags): array
    {
        foreach ($tags as &$tag) {
            if ($tag === '') {
                $tag = '""';
            } elseif (is_bool($tag)) {
                $tag = ($tag ? "true" : "false");
            } elseif (is_null($tag)) {
                $tag = ("null");
            }
        }

        return $tags;
    }

    /**
     * Change values by InfluxDB protocol
     *
     * @param array $fields
     *
     * @return array
     */
    private function prepareFields(array $fields): array
    {
        foreach ($fields as &$field) {
            if (is_integer($field)) {
                $field = sprintf('%d', $field);
            } elseif (is_string($field)) {
                $field = $this->escapeFieldValue($field);
            } elseif (is_bool($field)) {
                $field = ($field ? "true" : "false");
            } elseif (is_null($field)) {
                $field = $this->escapeFieldValue("null");
            }
        }

        return $fields;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function escapeFieldValue(string $value): string
    {
        $escapedValue = str_replace('"', '\"', $value);

        return sprintf('"%s"', $escapedValue);
    }

}