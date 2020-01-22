<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFJoinerBundle\loader;

use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Exception\JoinerException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class JoinerLoaderTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFJoinerBundle\loader
 */
final class JoinerLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader\JoinerLoader::get
     */
    public function testGetErr(): void
    {
        $loader = self::$container->get('hbpf.loader.joiner');

        self::expectException(JoinerException::class);
        $loader->get('null2');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader\JoinerLoader
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader\JoinerLoader::getAllJoiners
     */
    public function testGetAllJoiners(): void
    {
        $loader = self::$container->get('hbpf.loader.joiner');

        $fields = $loader->getAllJoiners();
        self::assertCount(1, $fields);

        $fields = $loader->getAllJoiners(['null']);
        self::assertCount(0, $fields);
    }

}
