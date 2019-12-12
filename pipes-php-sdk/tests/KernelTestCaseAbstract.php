<?php declare(strict_types=1);

namespace Tests;

use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package Tests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    use PrivateTrait;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

}
