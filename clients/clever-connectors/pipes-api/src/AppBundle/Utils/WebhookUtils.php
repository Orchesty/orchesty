<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 7:51
 */

namespace CleverConnectors\AppBundle\Utils;

/**
 * Class WebhookUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class WebhookUtils
{

    /**
     * WebhookUtils constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param string $domain
     * @param string $userId
     * @param string $token
     * @param string $nodeName
     * @param string $topologyName
     *
     * @return string
     */
    public static function getWebhookUrl(
        string $domain,
        string $userId,
        string $token,
        string $nodeName,
        string $topologyName
    ): string
    {
        return sprintf('%s/webhook/%s/%s/%s/%s', rtrim($domain, '/'), $userId, $token, $nodeName, $topologyName);
    }

}