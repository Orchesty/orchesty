<?php declare(strict_types=1);

namespace Tests\Unit\HbPFCustomNodeBundle\Loader;

use Exception;
use Hanaboso\PipesFramework\CustomNode\Impl\NullCustomNode;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Loader\CustomNodeLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class CustomNodeLoaderTest
 *
 * @package Tests\Unit\HbPFCustomNodeBundle\Loader
 */
final class CustomNodeLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var CustomNodeLoader
     */
    private $customNodeLoader;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customNodeLoader = self::$container->get('hbpf.loader.custom_node');
    }

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

}
