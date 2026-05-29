<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\RestoreErrorHandlersTrait;
use phpmock\phpunit\PHPMock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package PipesFrameworkTests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    use PHPMock;
    use PrivateTrait;
    use RestoreErrorHandlersTrait;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();

        $this->restoreErrorHandler();
        $this->restoreExceptionHandler();
    }

}
