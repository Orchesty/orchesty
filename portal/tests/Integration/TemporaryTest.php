<?php declare(strict_types=1);

namespace PortalTests\Integration;

use PortalTests\KernelTestCaseAbstract;

/**
 * Class TemporaryTest
 *
 * @package PortalTests\Integration
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
