<?php declare(strict_types=1);

namespace Tests\Integration\HbPFCustomNodeBundle\loader;

use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Loader\CustomNodeLoader;
use Tests\KernelTestCaseAbstract;

/**
 * Class CustomNodeLoaderTest
 *
 * @package Tests\Integration\HbPFCustomNodeBundle\loader
 */
final class CustomNodeLoaderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetAllCustomNodes(): void
    {
        $connector = new CustomNodeLoader($this->ownContainer);

        $fields = $connector->getAllCustomNodes();
        self::assertCount(7, $fields);

        $fields = $connector->getAllCustomNodes(['null']);
        self::assertCount(6, $fields);
    }

}