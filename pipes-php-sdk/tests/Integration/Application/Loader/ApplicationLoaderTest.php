<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Loader;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ApplicationLoaderTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Loader
 */
#[CoversClass(ApplicationLoader::class)]
final class ApplicationLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testGetApplication(): void
    {
        $loader = self::getContainer()->get('hbpf.application.loader');

        self::expectException(ApplicationInstallException::class);
        $loader->getApplication('test');
    }

    /**
     * @return void
     */
    public function testGetApplications(): void
    {
        $loader = self::getContainer()->get('hbpf.application.loader');

        self::assertEquals(['null', 'null2', 'null3'], $loader->getApplications());
    }

}
