<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\CustomNode;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\WorkerApi\Client;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class CustomNodeAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\CustomNode
 */
final class CustomNodeAbstractTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var TestNullCustomNode
     */
    private TestNullCustomNode $nullConnector;

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::setApplication
     * @throws Exception
     */
    public function testSetApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());

        self::assertEquals('null-key', $this->nullConnector->getApplicationKey());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationKey
     * @throws Exception
     */
    public function testGetApplicationKey(): void
    {
        self::expectException(CustomNodeException::class);
        self::expectExceptionMessage('Application has not set.');
        $this->nullConnector->getApplicationKey();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationKey
     * @throws Exception
     */
    public function testGetApplicationKeyWithApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());
        self::assertEquals('null-key',$this->nullConnector->getApplicationKey());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplication

     * @throws Exception
     */
    public function testGetApplicationException(): void
    {
        self::expectException(CustomNodeException::class);
        $this->nullConnector->getApplication();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplication

     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());
        self::assertNotEmpty($this->nullConnector->getApplication());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationInstall

     * @throws Exception
     */
    public function testGetAppInstall(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());
        self::expectExceptionMessage('Application [null-key] was not found');
        $this->invokeMethod($this->nullConnector, 'getApplicationInstall', [NULL]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationInstallFromProcess

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
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationInstallFromProcess

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
