<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\KernelTestCaseAbstract;

/**
 * Class TemporaryTest
 *
 * @package Tests\Integration
 */
final class TemporaryTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testBasic(): void
    {
        self::assertCount(0, []);
    }

}
