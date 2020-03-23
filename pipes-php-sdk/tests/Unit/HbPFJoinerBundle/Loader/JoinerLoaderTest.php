<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\HbPFJoinerBundle\Loader;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader\JoinerLoader;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class JoinerLoaderTest
 *
 * @package PipesPhpSdkTests\Unit\HbPFJoinerBundle\Loader
 */
final class JoinerLoaderTest extends KernelTestCaseAbstract
{

    /**
     * @var JoinerLoader
     */
    private $loader;

    /**
     *
     */
    function setUp(): void
    {
        parent::setUp();

        $this->loader = self::$container->get('hbpf.loader.joiner');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader\JoinerLoader::get
     *
     * @throws Exception
     */
    public function testLoader(): void
    {
        $this->loader->get('null');
        self::assertFake();
    }

}
