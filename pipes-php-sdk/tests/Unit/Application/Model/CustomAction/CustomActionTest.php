<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Application\Model\CustomAction;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Model\CustomAction\CustomAction;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class CustomActionTest
 *
 * @package PipesPhpSdkTests\Unit\Application\Model\CustomAction
 */
final class CustomActionTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\CustomAction\CustomAction::toArray
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\CustomAction\CustomAction::getBody
     *
     * @throws Exception
     */
    public function testCustomAction(): void
    {
        $actionOpen         = new CustomAction('testName', CustomAction::ACTION_OPEN, 'https://www.google.com/');
        $actionCall         = new CustomAction(
            'testName',
            CustomAction::ACTION_CALL,
            'https://www.google.com/',
            'body',
        );
        $actionCallTopology = new CustomAction(
            'testName',
            CustomAction::ACTION_CALL,
            NULL,
            'body',
            'testTopology',
            'testNode',
        );

        self::assertEquals(6, count($actionCall->toArray()));
        self::assertEquals(6, count($actionOpen->toArray()));
        self::assertEquals(NULL, $actionOpen->getBody());
        self::assertEquals(NULL, $actionCallTopology->getUrl());
    }

}
