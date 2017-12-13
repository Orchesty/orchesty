<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Hanaboso\PipesFramework\Utils\StringUtil;
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
     * @param ContainerBuilder $container
     *
     * @throws SystemException
     */
    public function process(ContainerBuilder $container): void
    {
        $loader = $container->findDefinition('cc.systems.loader');
        foreach ($container->getParameter('systems.tags') as $tagWithPercentage) {
            $tagWithoutPercentage = $container->getParameter(Strings::substring($tagWithPercentage, 1, -1));

            $method = sprintf(
                'setSystemsWithTag%s',
                StringUtil::toCamelCase($tagWithoutPercentage)
            );

            if (method_exists(SystemLoader::class, $method)) {
                $services = $container->findTaggedServiceIds($tagWithPercentage);
                $services = array_keys($services);
                if ($container->getParameter('kernel.environment') === 'prod') {
                    $developmentTag      = sprintf('%%%s%%', $container->getParameter('systems.dev'));
                    $developmentServices = $container->findTaggedServiceIds($developmentTag);
                    $developmentServices = array_keys($developmentServices);
                    foreach ($services as $key => $service) {
                        if (in_array($service, $developmentServices)) {
                            unset($services[$key]);
                        }
                    }
                }

                $loader->addMethodCall($method, [$services]);
            } else {
                throw new SystemException(
                    sprintf('System method \'%s\' not found', $method),
                    SystemException::SYSTEM_METHOD_NOT_FOUND
                );
            }
        }
    }

}