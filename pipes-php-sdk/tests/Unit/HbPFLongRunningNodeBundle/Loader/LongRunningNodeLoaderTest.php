<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFLongRunningNodeBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class LongRunningNodeLoaderTest
 *
 * @package PipesPhpSdkTests\Unit\HbPFLongRunningNodeBundle\Loader
 */
final class LongRunningNodeLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var LongRunningNodeLoader
     */
    private LongRunningNodeLoader $loader;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader::getLongRunningNode
     *
     * @throws Exception
     */
    public function testLoader(): void
    {
        $testClass = new TestLongRunningNode();
        self::$container->set('hbpf.long_running.test', $testClass);

        self::expectException(LongRunningNodeException::class);
        self::expectExceptionCode(LongRunningNodeException::LONG_RUNNING_SERVICE_NOT_FOUND);
        $this->loader->getLongRunningNode('another_test');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader::getLongRunningNode

     * @throws Exception
     */
    public function testGetLongRunningNode(): void
    {
        $node = $this->loader->getLongRunningNode('null');

        self::assertEquals('test', $node->getId());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader::getAllLongRunningNodes
     */
    public function testGetAllLongRunningNodes(): void
    {
        self::assertEquals([], $this->loader->getAllLongRunningNodes(['null']));
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = self::$container->get('hbpf.loader.long_running');
    }

}
