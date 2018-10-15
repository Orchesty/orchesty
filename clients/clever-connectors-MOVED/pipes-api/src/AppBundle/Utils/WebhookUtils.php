<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 7:51
 */

namespace CleverConnectors\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Traits\StaticTrait;

/**
 * Class WebhookUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class WebhookUtils
{

    use StaticTrait;

    /**
     * @param string        $domain
     * @param SystemInstall $systemInstall
     * @param string        $nodeName
     * @param string        $topologyName
     *
     * @return string
     */
    public static function getWebhookUrl(
        string $domain,
        SystemInstall $systemInstall,
        string $nodeName,
        string $topologyName
    ): string
    {
        return sprintf(
            '%s/webhook/%s/%s/%s/%s',
            rtrim($domain, '/'),
            $systemInstall->getUser(),
            $systemInstall->getToken(),
            $nodeName,
            $topologyName
        );
    }

}