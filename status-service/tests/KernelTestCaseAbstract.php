<?php declare(strict_types=1);

namespace PipesStatusServiceTests;

use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package PipesStatusServiceTests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    use PrivateTrait;
    use CustomAssertTrait;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

}
