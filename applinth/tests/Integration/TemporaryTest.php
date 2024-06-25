<?php declare(strict_types=1);

namespace ApplinthTests\Integration;

use ApplinthTests\KernelTestCaseAbstract;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;

/**
 * Class TemporaryTest
 *
 * @package ApplinthTests\Integration
 */
final class TemporaryTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     *
     */
    public function testBasic(): void
    {
        self::assertFake();
    }

}
