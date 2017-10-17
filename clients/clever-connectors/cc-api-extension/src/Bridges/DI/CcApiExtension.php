<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 10:54 AM
 */

namespace CcApi\Bridges\DI;

use CcApi\Connector\ConnectorManager;
use CcApi\Curl\ClientFactory;
use CcApi\Curl\CurlSender;
use Nette\DI\CompilerExtension;

/**
 * Class CcApiExtension
 *
 * @package CcApi\Bridges\CcApiDI
 */
class CcApiExtension extends CompilerExtension
{

    /**
     * Default config
     */
    private const DEFAULT_CONFIG = [
        'base_uri' => '',
        'timeout'  => '30',
        'cert'     => '',
        'logger'   => FALSE,
    ];

    /**
     *
     */
    public function loadConfiguration(): void
    {
        $config = $this->validateConfig(self::DEFAULT_CONFIG);

        $clientConfig['timeout']  = $config['timeout'] ?? self::DEFAULT_CONFIG['timeout'];
        $clientConfig['base_uri'] = $config['base_uri'] ?? self::DEFAULT_CONFIG['base_uri'];

        $builder = $this->getContainerBuilder();
        $builder
            ->addDefinition($this->prefix('guzzle.client.factory'))
            ->setFactory(ClientFactory::class, [$clientConfig]);

        $builder
            ->addDefinition($this->prefix('curl.sender'))
            ->setFactory(CurlSender::class, [$this->prefix('@guzzle.client.factory'), $config['cert']]);

        if ($config['logger'] === TRUE) {
            $builder
                ->getDefinition($this->prefix('curl.sender'))
                ->addSetup('setLogger', ['@logger']);
        }

        $builder
            ->addDefinition($this->prefix('connector.manager'))
            ->setFactory(ConnectorManager::class, [$this->prefix('@curl.sender')]);
    }

}