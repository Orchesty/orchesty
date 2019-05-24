<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Trait TestCaseTrait
 *
 * @package Tests
 */
trait TestCaseTrait
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     *
     */
    private function prepareDatabase(): void
    {
        $database = sprintf('%s-%s', self::$container->getParameter('mongo_db'), getenv('TEST_TOKEN'));

        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getConnection()->dropDatabase($database);
        $this->dm->getConfiguration()->setDefaultDB($database);
    }

}
