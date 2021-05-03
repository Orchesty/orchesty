<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Application\Utils;

use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Utils\ApplicationUtils;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ApplicationUtilsTest
 *
 * @package PipesPhpSdkTests\Unit\Application\Utils
 */
final class ApplicationUtilsTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Utils\ApplicationUtils::generateUrl
     */
    public function testGenerateUrl(): void
    {
        $applicationInstall = (new ApplicationInstall())->setUser('user')->setKey('key');

        self::assertEquals(
            '/api/applications/key/users/user/authorize/token',
            ApplicationUtils::generateUrl($applicationInstall),
        );
    }

}
