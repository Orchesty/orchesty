<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Imp;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\NullCustomNode;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

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

}
