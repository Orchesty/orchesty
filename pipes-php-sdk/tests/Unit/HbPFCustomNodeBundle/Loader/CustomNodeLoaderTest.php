<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFCustomNodeBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\NullCustomNode;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Loader\CustomNodeLoader;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class CustomNodeLoaderTest
 *
 * @package PipesPhpSdkTests\Unit\HbPFCustomNodeBundle\Loader
 */
final class CustomNodeLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var CustomNodeLoader
     */
    private CustomNodeLoader $customNodeLoader;

    /**
     * @throws Exception
     */
    public function testLoadCustomNode(): void
    {
        $customNode = $this->customNodeLoader->get('null');

        self::assertInstanceOf(NullCustomNode::class, $customNode);
    }

    /**
     * @throws Exception
     */
    public function testLoadMissingCustomNode(): void
    {
        self::expectException(CustomNodeException::class);
        self::expectExceptionCode(CustomNodeException::CUSTOM_NODE_SERVICE_NOT_FOUND);

        $this->customNodeLoader->get('missing');
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customNodeLoader = self::$container->get('hbpf.loader.custom_node');
    }

}
