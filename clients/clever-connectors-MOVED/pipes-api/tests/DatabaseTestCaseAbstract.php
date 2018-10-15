<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;

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
     * DatabaseTestCaseAbstract constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = NULL, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        self::bootKernel();
        $this->dm = $this->ownContainer->get('doctrine_mongodb.odm.default_document_manager');
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dm->getConnection()->dropDatabase('clever-connectors');
    }

    /**
     * @param object $document
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush($document);
    }

}
