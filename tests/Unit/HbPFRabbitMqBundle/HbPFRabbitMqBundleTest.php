<?php declare(strict_types=1);

namespace Tests\Unit\HbPFRabbitMqBundle;

use Exception;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DependencyInjection\Compiler\RabbitMqCompilerPass;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DependencyInjection\RabbitMqExtension;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\HbPFRabbitMqBundle;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class HbPFRabbitMqBundleTest
 *
 * @package Tests\Unit\HbPFRabbitMqBundle
 */
final class HbPFRabbitMqBundleTest extends KernelTestCaseAbstract
{

    /**
     * @var HbPFRabbitMqBundle
     */
    protected $bundle;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->bundle = new HbPFRabbitMqBundle();
    }

    /**
     * @return void
     */
    public function testGetContainerExtension(): void
    {
        self::assertInstanceOf(
            RabbitMqExtension::class,
            $this->bundle->getContainerExtension()
        );
    }

    /**
     * @return void
     */
    public function testBuild(): void
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
     * @return void
     * @throws Exception
     */
    public function testRegisterCommands(): void
    {
        /** @var Application|MockObject $application */
        $application = self::createMock(Application::class);

        /** @var ContainerInterface|MockObject $container */
        $container = self::createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function ($id) {
                if (in_array($id, [
                        'command.rabbit-mq.setup',
                        'command.rabbit-mq.consumer',
                        'command.rabbit-mq.async-consumer',
                    ]
                )) {
                    return new Command($id);
                }

                throw new InvalidArgumentException(
                    sprintf('Service \'%s\' does not exist.', $id)
                );
            });

        $this->bundle->setContainer($container);
        $this->bundle->registerCommands($application);
    }

}
