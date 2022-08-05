<?php declare(strict_types=1);

namespace ApplinthTests\Integration;

use ApplinthTests\KernelTestCaseAbstract;

/**
 * Class TemporaryTest
 *
 * @package ApplinthTests\Integration
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
