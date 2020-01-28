<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Imp;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\NullCustomNode;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;

/**
 * Class NullCustomNodeTest
 *
 * @package PipesPhpSdkTests\Integration\CustomNode\Imp
 */
final class NullCustomNodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\Impl\NullCustomNode::process
     */
    public function testProcess(): void
    {
        $dto = new ProcessDto();
        (new NullCustomNode())->process($dto);

        self::assertEquals(['pf-result-message' => 'Null worker resending data.'], $dto->getHeaders());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract::getApplication
     * @throws Exception
     */
    public function testGetApplicationException(): void
    {
        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::MISSING_APPLICATION);
        (new NullCustomNode())->getApplication();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract::getApplication
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $node = new NullCustomNode();

        $node->setApplication(new TestNullApplication());
        self::assertNotEmpty($node->getApplication());
    }

}
