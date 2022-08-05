<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Loader;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ApplicationLoaderTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Loader
 */
final class ApplicationLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader::getApplication
     */
    public function testGetApplication(): void
    {
        $loader = self::getContainer()->get('hbpf.application.loader');

        self::expectException(ApplicationInstallException::class);
        $loader->getApplication('test');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader::getApplications
     */
    public function testGetApplications(): void
    {
        $loader = self::getContainer()->get('hbpf.application.loader');

        self::assertEquals(['null', 'null2', 'null3'], $loader->getApplications());
    }

}
