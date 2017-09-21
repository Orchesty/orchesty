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
     * SystemCompilerPass constructor.
     *
     * @param string[] $tags
     */
    public function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws SystemException
     */
    public function process(ContainerBuilder $container): void
    {
        $service = $container->findDefinition('systems.loader');
        foreach ($this->tags as $tag) {
            $method = sprintf('setSystemsWithTag%s', Strings::firstUpper($tag));
            if (method_exists(SystemLoader::class, $method)) {
                $service->addMethodCall($method, [$container->findTaggedServiceIds($tag)]);
            } else {
                throw new SystemException(
                    sprintf('System method \'%s\' not found', $method),
                    SystemException::SYSTEM_METHOD_NOT_FOUND
                );
            }
        }
    }

}