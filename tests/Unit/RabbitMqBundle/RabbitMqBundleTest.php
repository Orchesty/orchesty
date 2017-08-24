<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 18:47
 */

namespace Tests\Unit\RabbitMqBundle;

use Hanaboso\PipesFramework\RabbitMqBundle\DependencyInjection\Compiler\RabbitMqCompilerPass;
use Hanaboso\PipesFramework\RabbitMqBundle\DependencyInjection\RabbitMqExtension;
use Hanaboso\PipesFramework\RabbitMqBundle\RabbitMqBundle;
use InvalidArgumentException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class RabbitMqBundleTest
 *
 * @package Tests\Unit\RabbitMqBundle
 */
class RabbitMqBundleTest extends KernelTestCaseAbstract
{

    /**
     * @var RabbitMqBundle
     */
    protected $bundle;

    /**
     *
     */
    protected function setUp()
    {
        $this->bundle = new RabbitMqBundle();
    }

    /**
     *
     */
    public function testGetContainerExtension()
    {
        $this->assertInstanceOf(
            RabbitMqExtension::class,
            $this->bundle->getContainerExtension()
        );
    }

    /**
     *
     */
    public function testBuild()
    {
        $containerBuilder = new ContainerBuilder();
        $this->bundle->build($containerBuilder);

        $passConfig         = $containerBuilder->getCompiler()->getPassConfig();
        $optimizationPasses = $passConfig->getOptimizationPasses();

        $contains = FALSE;
        foreach ($optimizationPasses as $pass) {
            if ($pass instanceof RabbitMqCompilerPass) {
                $contains = TRUE;
            }
        }

        if (!$contains) {
            $this->fail("Bunny hasn't registered compiler pass.");
        }
    }

    /**
     *
     */
    public function testRegisterCommands()
    {
        $application = $this->getMockBuilder(Application::class)->getMock();
        $container   = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method("get")->willReturnCallback(function ($id) {
            if (in_array($id, [
                    "command.rabbit-mq.setup",
                    "command.rabbit-mq.consumer",
                ]
            )) {
                return new Command($id);
            }

            throw new InvalidArgumentException("Service '{$id}' does not exist.");
        });

        /** @var Application $application */
        /** @var ContainerInterface $container */
        $this->bundle->setContainer($container);
        $this->bundle->registerCommands($application);
    }

}
