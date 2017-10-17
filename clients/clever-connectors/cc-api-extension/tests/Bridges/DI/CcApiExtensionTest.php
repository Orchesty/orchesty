<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 11:15 AM
 */

namespace Tests\Bridges\DI;

use CcApi\Bridges\DI\CcApiExtension;
use CcApi\Connector\ConnectorInterface;
use CcApi\Curl\ClientFactory;
use CcApi\Curl\CurlSender;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use PHPUnit\Framework\TestCase;

/**
 * Class CcApiExtensionTest
 *
 * @package Tests\Bridges\CcApiDI
 */
class CcApiExtensionTest extends TestCase
{

    /**
     * @covers CcApiExtension::loadConfiguration()
     */
    public function testCreateServices(): void
    {
        $loader = new ContainerLoader(__DIR__ . '/../../../temp', TRUE);
        $class  = $loader->load(function (Compiler $compiler): void {
            $compiler->addExtension('cc_api', new CcApiExtension());
        });
        /** @var Container $container */
        $container = new $class;

        $this->assertInstanceOf(ClientFactory::class, $container->getService('cc_api.guzzle.client.factory'));
        $this->assertInstanceOf(CurlSender::class, $container->getService('cc_api.curl.sender'));
        $this->assertInstanceOf(ConnectorInterface::class, $container->getService('cc_api.connector.manager'));
    }

}