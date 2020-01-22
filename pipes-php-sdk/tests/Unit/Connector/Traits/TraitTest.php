<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector\Traits;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use ReflectionException;

/**
 * Class TraitTest
 *
 * @package PipesPhpSdkTests\Unit\Connector\Traits
 */
final class TraitTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var TestNullConnector
     */
    private TestNullConnector $nullConnector;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait::processAction
     *
     * @throws ConnectorException
     */
    public function testProcessActionException(): void
    {
        self::expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION);
        $this->nullConnector->processAction(new ProcessDto());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait::processEvent
     *
     * @throws ConnectorException
     */
    public function testProcessEventException(): void
    {
        self::expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
        $this->nullConnector->processEvent(new ProcessDto());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait::createException
     *
     * @throws ReflectionException
     */
    public function testCreateException(): void
    {
        $exception = $this->invokeMethod(
            $this->nullConnector,
            'createException',
            ['%s. This is test exception', 'Uupps']
        );

        self::assertEquals("Connector 'null-test-trait': Uupps. This is test exception", $exception->getMessage());
        self::assertEquals(ConnectorException::CONNECTOR_FAILED_TO_PROCESS, $exception->getCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait::createMissingContentException
     *
     * @throws ReflectionException
     */
    public function testCreateMissingContentException(): void
    {
        $exception = $this->invokeMethod(
            $this->nullConnector,
            'createMissingContentException',
            ['Something']
        );

        self::assertEquals(
            "Connector 'null-test-trait': Content 'Something' does not exist!",
            $exception->getMessage()
        );
        self::assertEquals(ConnectorException::CONNECTOR_FAILED_TO_PROCESS, $exception->getCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait::createMissingHeaderException
     *
     * @throws ReflectionException
     */
    public function testCreateMissingHeaderException(): void
    {
        $exception = $this->invokeMethod(
            $this->nullConnector,
            'createMissingHeaderException',
            ['Something']
        );

        self::assertEquals(
            "Connector 'null-test-trait': Header 'Something' does not exist!",
            $exception->getMessage()
        );
        self::assertEquals(ConnectorException::CONNECTOR_FAILED_TO_PROCESS, $exception->getCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait::createMissingApplicationInstallException
     *
     * @throws ReflectionException
     */
    public function testCreateMissingApplicationInstallException(): void
    {
        $exception = $this->invokeMethod(
            $this->nullConnector,
            'createMissingApplicationInstallException',
            ['Something']
        );

        self::assertEquals(
            "Connector 'null-test-trait': ApplicationInstall with key 'Something' does not exist!",
            $exception->getMessage()
        );
        self::assertEquals(ConnectorException::CONNECTOR_FAILED_TO_PROCESS, $exception->getCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait::createRepeatException
     *
     * @throws ReflectionException
     */
    public function testCreateRepeatException(): void
    {
        $exception = $this->invokeMethod(
            $this->nullConnector,
            'createRepeatException',
            [new ProcessDto(), new Exception('Upps. Something went wrong.'), 70_000, 15]
        );

        self::assertEquals(
            "Connector 'null-test-trait': Exception: Upps. Something went wrong.",
            $exception->getMessage()
        );
        self::assertEquals(15, $exception->getMaxHops());
        self::assertEquals(70_000, $exception->getInterval());
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->nullConnector = new TestNullConnector();
    }

}
