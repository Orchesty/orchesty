<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatabaseTestCase
 *
 * @package Tests
 */
class DatabaseTestCase extends KernelTestCase
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * DatabaseTestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();
        self::bootKernel();
        $this->container       = self::$kernel->getContainer();
        $this->documentManager = $this->container->get('doctrine_mongodb.odm.default_document_manager');
    }

}