<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle;

use CleverConnectors\AppBundle\Model\Systems\SystemCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class AppBundle
 *
 * @package CleverConnectors\AppBundle
 */
class AppBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SystemCompilerPass());
    }

}