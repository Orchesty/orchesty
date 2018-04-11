<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CleverCore\SocialMultichannel\DI\SocialMultichannelExtension;
use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use Nette\DI\Container;

/**
 * Class AdModuleLoader
 *
 * @package CleverCore\SocialMultichannel\Models
 */
class AdModuleLoader
{

    private const DEFAULT_PREFIX = 'module';

    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $prefix;

    /**
     * AdModuleLoader constructor.
     *
     * @param Container $container
     * @param string    $prefix
     */
    public function __construct(Container $container, string $prefix = self::DEFAULT_PREFIX)
    {
        $this->container = $container;
        $this->prefix    = $prefix;
    }

    /**
     * @param string $type
     *
     * @return AdModuleInterface
     */
    public function loadModule(string $type): AdModuleInterface
    {
        $type = AdTypeEnum::isValid($type);

        $name = sprintf('%s.%s.%s', SocialMultichannelExtension::NAME, $this->prefix, $type);

        /** @var AdModuleInterface $adModule */
        $adModule =  $this->container->getService($name);

        return $adModule;
    }

}