<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Loader;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use HbPFAppStoreTests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationLoaderTest
 *
 * @package HbPFAppStoreTests\Integration\Loader
 */
final class ApplicationLoaderTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Loader\ApplicationLoader::getApplication
     * @throws ApplicationInstallException
     */
    public function testGetApplication(): void
    {
        $loader = self::$container->get('hbpf.loader.application');

        self::expectException(ApplicationInstallException::class);
        $loader->getApplication('app');
    }

}
