<?php declare(strict_types=1);

namespace PipesPhpSdkTests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package PipesPhpSdkTests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    use PrivateTrait;
    use CustomAssertTrait;

    /**
     * @var DocumentManager
     */
    protected DocumentManager $dm;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
    }

}
