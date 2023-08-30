<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\TopologyInstaller\Dto;

use Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile;
use PipesFrameworkTests\KernelTestCaseAbstract;
use RuntimeException;

/**
 * Class TopologyFileTest
 *
 * @package PipesFrameworkTests\Unit\TopologyInstaller\Dto
 */
final class TopologyFileTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getPath
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getFileContents
     */
    public function testGetPath(): void
    {
        $this->getFunctionMock('Hanaboso\PipesFramework\TopologyInstaller\Dto', 'file_get_contents')
            ->expects(self::any())
            ->willReturn(FALSE);

        $dto = new TopologyFile('name', __DIR__ . '/data/test.txt');
        self::assertEquals('/var/www/tests/Unit/TopologyInstaller/Dto/data/test.txt', $dto->getPath());

        self::expectException(RuntimeException::class);
        $dto->getFileContents();
    }

    /**
     * @covers \Hanaboso\PipesFramework\TopologyInstaller\Dto\TopologyFile::getFileContents
     */
    public function testGetFileContentErr(): void
    {
        $dto = new TopologyFile('name', __DIR__ . '/doesnt_exist.txt');

        self::expectException(RuntimeException::class);
        $dto->getFileContents();
    }

}
