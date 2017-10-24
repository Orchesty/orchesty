<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.10.17
 * Time: 7:53
 */

namespace Tests\Unit\AppBundle\Utils;

use CleverConnectors\AppBundle\Utils\WebhookUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class WebhookUtilsTest
 *
 * @package Tests\Unit\AppBundle\Utils
 */
final class WebhookUtilsTest extends TestCase
{

    /**
     *
     */
    public function testGetUrl(): void
    {
        $expected = 'http://127.0.0.1/webhook/usr-guid/tok-en/node-name/topo-name';
        $result   = WebhookUtils::getWebhookUrl('http://127.0.0.1/', 'usr-guid', 'tok-en', 'node-name', 'topo-name');

        self::assertEquals($expected, $result);
    }

}