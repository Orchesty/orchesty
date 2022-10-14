<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Loader;

use Hanaboso\CommonsBundle\Utils\NodeServiceLoader;
use Hanaboso\PipesPhpSdk\Application\Document\Dto\CommonObjectDto;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * Class CustomNodeLoader
 *
 * @package Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Loader
 */
final class CustomNodeLoader
{

    public const PREFIX = 'hbpf.custom_node';

    /**
     * CustomNodeLoader constructor.
     *
     * @param ContainerInterface $container
     */
    function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param string $serviceName
     *
     * @return CommonNodeAbstract
     * @throws CustomNodeException
     */
    public function get(string $serviceName): CommonNodeAbstract
    {
        $name = sprintf('%s.%s', self::PREFIX, $serviceName);
        if ($this->container->has($name)) {
            /** @var CommonNodeAbstract $node */
            $node = $this->container->get($name);

            return $node;
        }

        throw new CustomNodeException(
            sprintf('Node [%s] not found.', $serviceName),
            CustomNodeException::CUSTOM_NODE_SERVICE_NOT_FOUND,
        );
    }

    /**
     * @param mixed[] $exclude
     *
     * @return mixed[]
     */
    public function getAllCustomNodes(array $exclude = []): array
    {
        $dirs = $this->container->getParameter('node_services_dirs');

        return NodeServiceLoader::getServices($dirs, self::PREFIX, $exclude);
    }

    /**
     * @return CommonObjectDto[]
     */
    public function getList(): array
    {
        $services = array_map(function($serviceName) {
            try {
                return $this->get($serviceName);
            } catch (Throwable) {
                return NULL;
            }
        }, self::getAllCustomNodes());

        $services = array_filter($services);

        return array_map(static function ($customNode) {
            try {
                $applicationName = $customNode->getApplication()->getName();
            } catch (Throwable) {
                $applicationName = NULL;
            }

            return new CommonObjectDto($customNode->getName(), $applicationName);
        }, $services);
    }

}
