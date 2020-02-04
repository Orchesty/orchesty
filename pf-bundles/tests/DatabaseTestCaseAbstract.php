<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\Utils\String\Json;
use Symfony\Component\HttpFoundation\Session\Session;

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
     * @var Session<mixed>
     */
    protected $session;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getClient()->dropDatabase('pipes');
        $this->session = new Session();
        $this->session->invalidate();
        $this->clearMongo();
    }

    /**
     * @param object $document
     *
     * @throws Exception
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /**
     * @param string  $path
     * @param mixed[] $arrayResult
     */
    protected function assertResult(string $path, array $arrayResult): void
    {
        $fileContent = file_get_contents($path);
        $i           = 0;

        foreach ($arrayResult['node_config'] as $key => $value) {
            $value;
            if (strlen((string) $key) === 24) {
                $arrayResult['node_config']
                [sprintf('5d6d17e1e7ad880000000000%s', $i)] = $arrayResult['node_config'][$key];
                $i++;
                unset($arrayResult['node_config'][$key]);
            }
        }

        self::assertEquals($fileContent, Json::encode($arrayResult));
    }

}
