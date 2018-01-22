<?php declare(strict_types=1);

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
     * UDPService constructor.
     *
     * @param UDPSender $sender
     * @param string    $measurement
     */
    public function __construct(UDPSender $sender, string $measurement)
    {
        $this->sender      = $sender;
        $this->measurement = $measurement;
    }

    /**
     * @param array $fields
     * @param array $tags
     *
     * @return bool
     */
    public function send(array $fields, array $tags = []): bool
    {
        return $this->sender->send($this->createMessage($fields, $tags));
    }

    /**
     * @param array $fields
     * @param array $tags
     *
     * @return string
     */
    public function createMessage(array $fields, array $tags = []): string
    {
        if (empty($fields)) {
            throw new InvalidArgumentException('The fields must not be empty.');
        }

        return sprintf(
            '%s,%s %s',
            $this->measurement,
            $this->join($this->prepareTags($tags)),
            $this->join($this->prepareFields($fields))
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

        if (empty($items)) {
            return $result;
        }

        foreach ($items as $key => $value) {
            $result .= sprintf('%s=%s,', $key, $value);
        }

        $result = substr($result, 0, -1);

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