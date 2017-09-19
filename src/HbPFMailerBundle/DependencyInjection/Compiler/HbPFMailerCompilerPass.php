<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: sep
 * Date: 15.9.17
 * Time: 22:22
 */

namespace Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class HbPFMailerCompilerPass
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection\Compiler
 */
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
    public function process(ContainerBuilder $container): void
    {
        $config = $container->getParameter('hbpf');
        if (!array_key_exists('mailer', $config) || !array_key_exists('default_values', $config['mailer'])) {
            throw new InvalidArgumentException(
                'Container doesn\'t have config parameter \'default_values\', HbPFMailerExtension probably haven\'t processed config.'
            );
        }

        $mailerConfig = $config['mailer'];
        $defaultValue = new Definition('Hanaboso\PipesFramework\HbPFMailerBundle\DefaultValues\DefaultValues', [
            $mailerConfig['default_values']['from'],
            $mailerConfig['default_values']['subject'],
            $mailerConfig['default_values']['to'],
            $mailerConfig['default_values']['bcc'],
        ]);

        $container->setDefinition($this->defaultValueServiceId, $defaultValue);
    }

}
