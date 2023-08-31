<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\TopologyInstaller;

use Hanaboso\PipesFramework\TopologyInstaller\TplgLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TplgLoaderTest
 *
 * @package PipesFrameworkTests\Integration\TopologyInstaller
 */
final class TplgLoaderTest extends TestCase
{

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\TplgLoader::load
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\TplgLoader::getName
     */
    public function testLoad(): void
    {
        $loader = new TplgLoader();
        $files  = $loader->load(sprintf('%s/data', __DIR__));

        self::assertCount(3, $files);
        self::assertInstanceOf(SplFileInfo::class, reset($files));
        self::assertArrayHasKey('file', $files);
        self::assertArrayHasKey('file2', $files);
        self::assertArrayHasKey('inner-file', $files);
    }

}
