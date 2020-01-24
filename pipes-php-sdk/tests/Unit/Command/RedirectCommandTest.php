<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Command;

use Hanaboso\PipesPhpSdk\Command\RedirectCommand;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class RedirectCommandTest
 *
 * @package PipesPhpSdkTests\Unit\Command
 */
final class RedirectCommandTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Command\RedirectCommand::make
     */
    public function testMake(): void
    {
        ob_start();
        (new RedirectCommand())->make('redirect/url');
        $content = ob_get_clean();

        self::assertStringContainsString('redirect/url', (string) $content);
    }

}
