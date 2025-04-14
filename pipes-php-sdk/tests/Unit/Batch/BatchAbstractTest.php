<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Batch;

use Exception;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BatchAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\Batch
 */
#[CoversClass(BatchAbstract::class)]
final class BatchAbstractTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var TestNullBatch
     */
    private TestNullBatch $nullBatchConnector;

    /**
     * @throws Exception
     */
    public function testEvaluateStatusCode(): void
    {
        $result = $this->nullBatchConnector->evaluateStatusCode(200, new BatchProcessDto());
        self::assertTrue($result);

        $result = $this->nullBatchConnector->evaluateStatusCode(400, new BatchProcessDto());
        self::assertFalse($result);
    }

    /**
     * @throws CustomNodeException
     */
    public function testSetApplication(): void
    {
        $this->nullBatchConnector->setApplication(new TestNullApplication());

        self::assertSame('null-key', $this->nullBatchConnector->getApplicationKey());
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationException(): void
    {
        self::expectException(CustomNodeException::class);
        $this->nullBatchConnector->getApplication();
    }

    /**
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $this->nullBatchConnector->setApplication(new TestNullApplication());
        self::assertNotEmpty($this->nullBatchConnector->getApplication());
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
        $this->nullBatchConnector     = new TestNullBatch($applicationInstallRepository);
    }

}
