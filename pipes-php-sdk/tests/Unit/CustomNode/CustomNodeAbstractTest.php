<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\CustomNode;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\WorkerApi\Client;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class CustomNodeAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\CustomNode
 */
#[CoversClass(CommonNodeAbstract::class)]
final class CustomNodeAbstractTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var TestNullCustomNode
     */
    private TestNullCustomNode $nullConnector;

    /**
     * @throws Exception
     */
    public function testSetApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());

        self::assertSame('null-key', $this->nullConnector->getApplicationKey());
    }

    /**
     * @throws Exception
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
    public function testGetApplicationKeyWithApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());
        self::assertSame('null-key',$this->nullConnector->getApplicationKey());
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
    public function testGetAppInstall(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());
        self::expectExceptionMessage('Application [null-key] was not found');
        $this->invokeMethod($this->nullConnector, 'getApplicationInstall', [NULL]);
    }

    /**
     * @throws Exception
     */
    public function testGetAppInstallFromProcess(): void
    {
        $dto = new ProcessDto();
        $dto->setUser('testUser');

        $this->nullConnector->setApplication(new TestNullApplication());
        self::expectExceptionMessage('Application [null-key] was not found');
        $this->invokeMethod($this->nullConnector, 'getApplicationInstallFromProcess', [$dto]);
    }

    /**
     * @throws Exception
     */
    public function testGetAppInstallFromProcessWithoutUser(): void
    {
        $dto = new ProcessDto();

        $this->nullConnector->setApplication(new TestNullApplication());
        self::expectExceptionMessage('User not defined');
        $this->invokeMethod($this->nullConnector, 'getApplicationInstallFromProcess', [$dto]);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $client = self::createMock(Client::class);

        $client->method('send')->willReturn(
            new Response(404, [], Json::encode(['message' => 'Application [null-key] was not found'])),
        );
        self::getContainer()->set('hbpf.worker-api', $client);

        /**
         * @var ApplicationInstallRepository $applicationInstallRepository
         */
        $applicationInstallRepository = self::getContainer()->get('hbpf.application_install.repository');
        $this->nullConnector          = new TestNullCustomNode($applicationInstallRepository);

        parent::setUp();
    }

}
