<?php declare(strict_types=1);

namespace CleverCore\Commons\DI;

use CleverCore\Commons\Curl\ClientFactory;
use CleverCore\Commons\Curl\CurlSender;
use CleverCore\Commons\Enums\DirectorySourceEnum;
use CleverCore\Commons\Model\DirectoryManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Gedmo\Tree\TreeListener;
use Nette\DI\CompilerExtension;

/**
 * Class CommonsExtension
 *
 * @package CleverCore\Commons\DI
 */
class CommonsExtension extends CompilerExtension
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
            ->addDefinition($this->prefix('directory.manager'))
            ->setFactory(DirectoryManager::class);
    }

    /**
     *
     */
    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        $builder
            ->addDefinition($this->prefix('gedmo.tree.listener'))
            ->setFactory(TreeListener::class)
            ->addTag('kdyby.subscriber');

        $builder
            ->getDefinition('doctrine.default.metadataDriver')
            ->addSetup('addDriver', [
                $builder
                    ->addDefinition($this->prefix('entities'))
                    ->setFactory(AnnotationDriver::class)
                    ->setArguments([
                        $builder->getDefinitionByType('Doctrine\Common\Annotations\Reader'),
                        [__DIR__ . '/../Entities'],
                    ]),
                mb_substr(__NAMESPACE__, 0, -3),
            ]);

        $connection = $builder->getDefinition('doctrine.default.connection');
        $setup      = $connection->getSetup();

        $setup[0]->arguments[0]['DirectorySourceEnum'] = 'DirectorySourceEnum';
        $setup[1]->arguments[0]['DirectorySourceEnum'] = DirectorySourceEnum::class;

        $connection->setSetup($setup);
    }

}