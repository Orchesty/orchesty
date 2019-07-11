<?php declare(strict_types=1);

namespace Tests\Integration\HbPFJoinerBundle\loader;

use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Loader\JoinerLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class JoinerLoaderTest
 *
 * @package Tests\Integration\HbPFJoinerBundle\loader
 */
final class JoinerLoaderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetAllJoiners(): void
    {
        $connector = new JoinerLoader(self::$container);

        $fields = $connector->getAllJoiners();
        self::assertCount(1, $fields);

        $fields = $connector->getAllJoiners(['null']);
        self::assertCount(0, $fields);
    }

}
