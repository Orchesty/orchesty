<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 2:45 PM
 */

namespace Tests\Unit\HbPFJoinerBundle\Loader;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Loader\JoinerLoader;
use Hanaboso\PipesFramework\Joiner\JoinerInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class JoinerLoaderTest
 *
 * @package Tests\Unit\HbPFJoinerBundle\Loader
 */
class JoinerLoaderTest extends KernelTestCaseAbstract
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
        $this->loader = $this->container->get('hbpf.loader.joiner');
    }

    /**
     * @covers JoinerLoader::get()
     */
    public function testLoader(): void
    {
        $joiner = $this->loader->get('null');
        self::assertInstanceOf(JoinerInterface::class, $joiner);
    }

}