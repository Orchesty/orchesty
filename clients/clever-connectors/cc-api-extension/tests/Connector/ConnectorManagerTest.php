<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 3:18 PM
 */

namespace Tests\Connector;

use CcApi\Connector\ConnectorManager;
use CcApi\Curl\ClientFactory;
use CcApi\Curl\CurlSender;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectorManagerTest
 *
 * @package Tests\Connector
 */
class ConnectorManagerTest extends TestCase
{

    /**
     *
     */
    public function testGetSystems(): void
    {
        $this->markTestIncomplete();
        $f = new ClientFactory(['base_uri' => 'http://private-ee2cb-cleverconnector1.apiary-mock.com']);
        //$f = new ClientFactory(['base_uri' => 'https://cleverconn.stage.hanaboso.net/']);
        //$c = new CurlSender($f, __DIR__ . '/stage-staf.pem');
        $c = new CurlSender($f);
        $m = new ConnectorManager($c);

        $s = $m->getAllSystems('xxx');

        var_dump($s);
    }

}