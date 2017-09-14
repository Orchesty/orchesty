<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:29 PM
 */

namespace Hanaboso\PipesFramework\HbPFMailerBundle;

use Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection\Compiler\HbPFMailerCompilerPass;
use Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection\HbPFMailerExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class HbPFMailerBundle
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle
 */
class HbPFMailerBundle extends Bundle
{

    /**
     * @return HbPFMailerExtension
     */
    public function getContainerExtension(): HbPFMailerExtension
    {
        if ($this->extension === NULL) {
            $this->extension = new HbPFMailerExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new HbPFMailerCompilerPass('hbpfmailer.default_value'),
            PassConfig::TYPE_OPTIMIZE
        );
    }

}
