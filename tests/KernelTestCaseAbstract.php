<?php declare(strict_types=1);

namespace Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package Tests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

}
