<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\TopologyInstaller\Dto;

use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\KernelTestCaseAbstract;
use RuntimeException;

/**
 * Class TopologyFileTest
 *
 * @package PipesFrameworkTests\Unit\TopologyInstaller\Dto
 */
#[CoversClass(TopologyFile::class)]
final class TopologyFileTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testGetPath(): void
    {
        $dto = new TopologyFile('name', __DIR__ . '/data/test.txt');
        self::assertSame('/var/www/tests/Unit/TopologyInstaller/Dto/data/test.txt', $dto->getPath());

        $dto->getFileContents();
    }

    /**
     * @return void
     */
    public function testGetFileContentErr(): void
    {
        $dto = new TopologyFile('name', __DIR__ . '/doesnt_exist.txt');

        self::expectException(RuntimeException::class);
        $dto->getFileContents();
    }

}
