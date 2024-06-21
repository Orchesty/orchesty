<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlClientFactory;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ConnectorAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\Connector
 */
#[CoversClass(ConnectorAbstract::class)]
final class ConnectorAbstractTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var TestNullConnector
     */
    private TestNullConnector $nullConnector;

    /**
     * @throws Exception
     */
    public function testEvaluateStatusCode(): void
    {
        $result = $this->nullConnector->evaluateStatusCode(200, new ProcessDto());
        self::assertTrue($result);

        $result = $this->nullConnector->evaluateStatusCode(400, new ProcessDto());
        self::assertFalse($result);
    }

    /**
     * @throws CustomNodeException
     */
    public function testSetApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());

        self::assertEquals('null-key', $this->nullConnector->getApplicationKey());
    }

    /**
     * @throws CustomNodeException
     */
    public function testGetApplicationKey(): void
    {
        self::expectException(CustomNodeException::class);
        self::expectExceptionMessage('Application has not set.');
        $this->nullConnector->getApplicationKey();
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationException(): void
    {
        self::expectException(CustomNodeException::class);
        $this->nullConnector->getApplication();
    }

    /**
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());
        self::assertNotEmpty($this->nullConnector->getApplication());
    }

    /**
     * @throws Exception
     */
    public function testGetSetSender(): void
    {
        $this->nullConnector->setSender(new CurlManager(new CurlClientFactory()));
        self::assertNotEmpty(self::getProperty($this->nullConnector,'sender'));
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * @var ApplicationInstallRepository $applicationInstallRepository
         */
        $applicationInstallRepository = self::getContainer()->get('hbpf.application_install.repository');
        $this->nullConnector          = new TestNullConnector($applicationInstallRepository);
    }

}
