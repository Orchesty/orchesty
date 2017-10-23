<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 14:54
 */

namespace Tests\Integration\AppBundle\Model\Command;

use CleverConnectors\AppBundle\Model\Command\AsyncCommandFactory;
use Exception;
use React\EventLoop\Factory;
use RuntimeException;
use Tests\KernelTestCaseAbstract;

/**
 * Class AsyncCommandFactoryTest
 *
 * @package Tests\Unit\AppBundle\Model\Command
 */
final class AsyncCommandFactoryTest extends KernelTestCaseAbstract
{

    /**
     * @var string
     */
    private $projectDir;

    /**
     *
     */
    public function setUp(): void
    {
        $this->projectDir = $this->container->getParameter('kernel.project_dir');
    }

    /**
     *
     */
    public function testRun(): void
    {
        $loop = Factory::create();

        $asyncCommandFactory = new AsyncCommandFactory($this->projectDir);

        $asyncCommandFactory
            ->create($loop, '--version')
            ->then(function (string $data) use ($loop): void {
                $this->assertStringStartsWith('Symfony', $data);
                $loop->stop();
            })->done();

        $loop->run();
    }

    /**
     *
     */
    public function testRunError(): void
    {
        $loop = Factory::create();

        $asyncCommandFactory = new AsyncCommandFactory($this->projectDir);

        $asyncCommandFactory
            ->create($loop, 'bla')
            ->then(NULL, function (Exception $e) use ($loop): void {
                $this->assertInstanceOf(RuntimeException::class, $e);
                $this->assertSame('Process exited with code 1.', $e->getMessage());
                $loop->stop();
            })->done();

        $loop->run();
    }

}