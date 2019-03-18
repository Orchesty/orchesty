<?php declare(strict_types=1);

namespace Tests\Integration\HbPFJoinerBundle\loader;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Loader\JoinerLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class JoinerLoaderTest
 */
final class JoinerLoaderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetAllJoiners(): void
    {
        $connector = new JoinerLoader($this->ownContainer);

        $fields = $connector->getAllJoiners();
        self::assertCount(1, $fields);

        $fields = $connector->getAllJoiners(['null']);
        self::assertCount(0, $fields);
    }

}