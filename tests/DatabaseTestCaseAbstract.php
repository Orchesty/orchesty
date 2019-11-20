<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Utils\Json;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package Tests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Session
     */
    protected $session;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getConnection()->dropDatabase('pipes');
        $this->session = new Session();
        $this->session->invalidate();
        $this->session->clear();
    }

    /**
     * @param object $document
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush($document);
    }

    /**
     * @param string $path
     * @param array  $arrayResult
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
