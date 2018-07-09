<?php declare(strict_types=1);

namespace Tests\Unit\HbPFCustomNodeBundle\Loader;

use Hanaboso\PipesFramework\CustomNode\Impl\NullCustomNode;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Loader\CustomNodeLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class CustomNodeLoaderTest
 *
 * @package Tests\Unit\HbPFCustomNodeBundle\Loader
 */
class CustomNodeLoaderTest extends KernelTestCaseAbstract
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
        $this->customNodeLoader = $this->ownContainer->get('hbpf.loader.custom_node');
    }

    /**
     *
     */
    public function testLoadCustomNode(): void
    {
        $customNode = $this->customNodeLoader->get('null');

        $this->assertInstanceOf(NullCustomNode::class, $customNode);
    }

    /**
     *
     */
    public function testLoadMissingCustomNode(): void
    {
        $this->expectException(CustomNodeException::class);
        $this->expectExceptionCode(CustomNodeException::CUSTOM_NODE_SERVICE_NOT_FOUND);

        $this->customNodeLoader->get('missing');
    }

}