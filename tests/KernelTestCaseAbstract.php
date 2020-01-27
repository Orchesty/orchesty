<?php declare(strict_types=1);

namespace NotificationSenderTests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package NotificationSenderTests
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
