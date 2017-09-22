<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Nette\Utils\Strings;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SystemCompilerPass
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
class SystemCompilerPass implements CompilerPassInterface
{

    /**
     * @var string[]
     */
    private $tags;

    /**
     * @var bool
     */
    private $isProduction;

    /**
     * SystemCompilerPass constructor.
     *
     * @param string[] $tags
     * @param bool     $isProduction
     */
    public function __construct(array $tags, bool $isProduction)
    {
        $this->tags         = $tags;
        $this->isProduction = $isProduction;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws SystemException
     */
    public function process(ContainerBuilder $container): void
    {
        $loader = $container->findDefinition('systems.loader');
        foreach ($this->tags as $tag) {
            $method = sprintf('setSystemsWithTag%s', implode('', array_map(function (string $part) {
                return Strings::firstUpper($part);
            }, explode('.', $tag))));
            if (method_exists(SystemLoader::class, $method)) {
                $services = $container->findTaggedServiceIds($tag);
                if ($this->isProduction) {
                    $developmentServices = $container->findTaggedServiceIds('systems.dev');
                    foreach ($services as $key => $service) {
                        if (in_array($service, $developmentServices, TRUE)) {
                            unset($services[$key]);
                        }
                    }
                }
                $loader->addMethodCall($method, [array_keys($services)]);
            } else {
                throw new SystemException(
                    sprintf('System method \'%s\' not found', $method),
                    SystemException::SYSTEM_METHOD_NOT_FOUND
                );
            }
        }
    }

}