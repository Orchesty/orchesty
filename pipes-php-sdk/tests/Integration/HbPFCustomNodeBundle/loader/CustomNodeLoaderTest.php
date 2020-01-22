<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFCustomNodeBundle\loader;

use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Loader\CustomNodeLoader;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class CustomNodeLoaderTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFCustomNodeBundle\loader
 */
final class CustomNodeLoaderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetAllCustomNodes(): void
    {
        $connector = new CustomNodeLoader(self::$container);

        $fields = $connector->getAllCustomNodes();
        self::assertCount(8, $fields);

        $fields = $connector->getAllCustomNodes(['null']);
        self::assertCount(7, $fields);
    }

}
