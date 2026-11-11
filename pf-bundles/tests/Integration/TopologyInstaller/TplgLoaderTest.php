<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\TopologyInstaller;

use Hanaboso\PipesFramework\TopologyInstaller\TplgLoader;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TplgLoaderTest
 *
 * @package PipesFrameworkTests\Integration\TopologyInstaller
 */
#[CoversClass(TplgLoader::class)]
final class TplgLoaderTest extends TestCase
{

    /**
     * @return void
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

        $first = reset($files);
        $data  = Json::decode($first->getContents());
        self::assertArrayHasKey('nodes', $data);
        self::assertArrayHasKey('connections', $data);
    }

}
