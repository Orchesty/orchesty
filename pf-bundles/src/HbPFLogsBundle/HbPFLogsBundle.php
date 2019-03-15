<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle;

use Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection\Compiler\LogsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class HbPFLogsBundle
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle
 */
class HbPFLogsBundle extends Bundle
{

    public const KEY = 'hb_pf_logs';

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new LogsCompilerPass());
    }

}
