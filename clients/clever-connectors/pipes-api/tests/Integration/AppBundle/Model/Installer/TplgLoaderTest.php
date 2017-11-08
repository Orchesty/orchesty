<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.11.17
 * Time: 13:36
 */

namespace Tests\Integration\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Model\Installer\TplgLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TplgLoaderTest
 *
 * @package Tests\Integration\AppBundle\Model\Installer
 */
final class TplgLoaderTest extends TestCase
{

    /**
     *
     */
    public function testLoad(): void
    {
        $loader = new TplgLoader();
        $files  = $loader->load(__DIR__ . '/data');

        self::assertCount(2, $files);
        self::assertInstanceOf(SplFileInfo::class, reset($files));
        self::assertArrayHasKey('file', $files);
        self::assertArrayHasKey('inner-file', $files);
    }

}