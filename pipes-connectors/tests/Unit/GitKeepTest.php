<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Unit;

use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class GitKeepTest
 *
 * @package HbPFConnectorsTests\Unit
 */
final class GitKeepTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     *
     */
    public function test(): void
    {
        self::assertFake();
    }

}
