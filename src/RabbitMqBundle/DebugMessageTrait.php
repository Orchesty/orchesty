<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 31.8.17
 * Time: 14:16
 */

namespace Hanaboso\PipesFramework\RabbitMqBundle;

/**
 * Trait DebugMessageTrait
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle
 */
trait DebugMessageTrait
{

    /**
     * @param null|string $string
     * @param null|string $exchange
     * @param null|string $routingKey
     * @param array|null  $headers
     *
     * @return array
     */
    public function prepareMessage(
        ?string $string = NULL,
        ?string $exchange = NULL,
        ?string $routingKey = NULL,
        ?array $headers = []
    ): array
    {
        $message = [];
        if ($string) {
            $message['message'] = $string;
        }

        if ($exchange) {
            $message['exchange'] = $exchange;
        }

        if ($routingKey) {
            $message['routing_key'] = $routingKey;
        }

        if (!empty($headers)) {
            $result = [];
            foreach ($headers as $key => $value) {
                $result[] = sprintf('%s=%s', $key, $value);
            }
            $message['headers'] = implode('@', $result);
        }

        return $message;
    }

}
