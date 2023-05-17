<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package PipesFrameworkTests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    use DatabaseTestTrait;
    use CustomAssertTrait;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
    }

    /**
     * @param string  $path
     * @param mixed[] $ar
     */
    protected function assertResult(string $path, array $ar): void
    {
        $fileContent = File::getContent($path);
        $i           = 0;

        foreach ($ar['node_config'] as $key => $value) {
            $value;
            if (strlen((string) $key) === 24) {
                $ar['node_config'][sprintf('5d6d17e1e7ad880000000000%s', $i)] = $ar['node_config'][$key];
                $i++;
                unset($ar['node_config'][$key]);
            }
        }

        self::assertEquals(Json::decode($fileContent ?: ''), $ar);
    }

}
