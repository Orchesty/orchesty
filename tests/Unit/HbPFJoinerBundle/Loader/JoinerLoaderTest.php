<?php declare(strict_types=1);

namespace Tests\Unit\HbPFJoinerBundle\Loader;

use Exception;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Loader\JoinerLoader;
use Hanaboso\PipesFramework\Joiner\JoinerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class JoinerLoaderTest
 *
 * @package Tests\Unit\HbPFJoinerBundle\Loader
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
     * @covers JoinerLoader::get()
     * @throws Exception
     */
    public function testLoader(): void
    {
        $joiner = $this->loader->get('null');
        self::assertInstanceOf(JoinerInterface::class, $joiner);
    }

}
