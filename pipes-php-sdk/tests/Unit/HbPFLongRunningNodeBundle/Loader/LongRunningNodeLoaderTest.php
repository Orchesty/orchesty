<?php declare(strict_types=1);

namespace Tests\Unit\HbPFLongRunningNodeBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesPhpSdk\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class LongRunningNodeLoaderTest
 *
 * @package Tests\Unit\HbPFLongRunningNodeBundle\Loader
 */
final class LongRunningNodeLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @covers LongRunningNodeLoader::getLongRunningNode()
     *
     * @throws Exception
     */
    public function testLoader(): void
    {
        $testClass = new TestLongRunningNode();
        self::$container->set('hbpf.long_running.test', $testClass);

        /** @var LongRunningNodeLoader $loader */
        $loader = self::$container->get('hbpf.loader.long_running');
        $res    = $loader->getLongRunningNode('test');
        self::assertInstanceOf(LongRunningNodeInterface::class, $res);

        self::expectException(LongRunningNodeException::class);
        self::expectExceptionCode(LongRunningNodeException::LONG_RUNNING_SERVICE_NOT_FOUND);
        $loader->getLongRunningNode('another_test');
    }

}
