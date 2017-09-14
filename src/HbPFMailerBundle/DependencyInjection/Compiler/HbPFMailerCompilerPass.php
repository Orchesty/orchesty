<?php
/**
 * Created by PhpStorm.
 * User: sep
 * Date: 15.9.17
 * Time: 22:22
 */

namespace Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class HbPFMailerCompilerPass implements CompilerPassInterface
{

    /**
     * @var string
     */
    protected $defaultValueServiceId;

    /**
     * HbPFMailerCompilerPass constructor.
     *
     * @param string $defaultValueServiceId
     */
    public function __construct($defaultValueServiceId)
    {
        $this->defaultValueServiceId = $defaultValueServiceId;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $config = $container->getParameter('hb_pf_mailer');
        if (!array_key_exists('default_values', $config)) {
            throw new InvalidArgumentException(
                'Container doesn\'t have config parameter \'default_values\', HbPFMailerExtension probably haven\'t processed config.'
            );
        }

        $defaultValue = new Definition('Hanaboso\PipesFramework\HbPFMailerBundle\DefaultValues\DefaultValues', [
            $config['default_values']['from'],
            $config['default_values']['subject'],
            $config['default_values']['to'],
            $config['default_values']['bcc'],
        ]);

        $container->setDefinition($this->defaultValueServiceId, $defaultValue);
    }

}
