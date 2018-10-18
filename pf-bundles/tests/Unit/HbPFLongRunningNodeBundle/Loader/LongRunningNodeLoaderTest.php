<?php declare(strict_types=1);

namespace Tests\Unit\HbPFLongRunningNodeBundle\Loader;

use Exception;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesFramework\LongRunningNode\Exception\LongRunningNodeException;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeInterface;
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
        $this->ownContainer->set('hbpf.long_running.test', $testClass);

        /** @var LongRunningNodeLoader $loader */
        $loader = $this->ownContainer->get('hbpf.loader.long_running');
        $res    = $loader->getLongRunningNode('test');
        self::assertInstanceOf(LongRunningNodeInterface::class, $res);

        $this->expectException(LongRunningNodeException::class);
        $this->expectExceptionCode(LongRunningNodeException::LONG_RUNNING_SERVICE_NOT_FOUND);
        $loader->getLongRunningNode('another_test');
    }

}