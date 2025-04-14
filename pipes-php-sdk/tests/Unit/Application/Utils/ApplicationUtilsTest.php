<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Application\Utils;

use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Utils\ApplicationUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ApplicationUtilsTest
 *
 * @package PipesPhpSdkTests\Unit\Application\Utils
 */
#[CoversClass(ApplicationUtils::class)]
final class ApplicationUtilsTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testGenerateUrl(): void
    {
        $applicationInstall = (new ApplicationInstall())->setUser('user')->setKey('key');

        self::assertSame(
            '/api/applications/key/users/user/authorize/token',
            ApplicationUtils::generateUrl($applicationInstall),
        );
    }

}
