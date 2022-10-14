<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
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
     */
    public function testSetApplication(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());

        self::assertEquals('null-key', $this->nullConnector->getApplicationKey());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationKey
     */
    public function testGetApplicationKey(): void
    {
        self::expectException(CustomNodeException::class);
        self::expectExceptionMessage('Application has not set.');
        $this->nullConnector->getApplicationKey();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationKey
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
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::setDb
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getDb

     * @throws Exception
     */
    public function testSetDb(): void
    {
        $this->nullConnector->setDb($this->dm);
        self::assertEquals($this->dm,$this->nullConnector->getDb());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::setDb
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getDb

     * @throws Exception
     */
    public function testSetNullDb(): void
    {
        $this->nullConnector->setDb(NULL);
        self::expectErrorMessage('MongoDbClient is not set.');
        $this->nullConnector->getDb();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationInstall

     * @throws Exception
     */
    public function testGetAppInstall(): void
    {
        $this->nullConnector->setDb($this->dm);
        $this->nullConnector->setApplication(new TestNullApplication());
        self::expectErrorMessage('Application [null-key] was not found');
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

        $this->nullConnector->setDb($this->dm);
        $this->nullConnector->setApplication(new TestNullApplication());
        self::expectErrorMessage('Application [null-key] was not found');
        $this->invokeMethod($this->nullConnector, 'getApplicationInstallFromProcess', [$dto]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getApplicationInstallFromProcess

     * @throws Exception
     */
    public function testGetAppInstallFromProcessWithoutUser(): void
    {
        $dto = new ProcessDto();

        $this->nullConnector->setDb($this->dm);
        $this->nullConnector->setApplication(new TestNullApplication());
        self::expectErrorMessage('User not defined');
        $this->invokeMethod($this->nullConnector, 'getApplicationInstallFromProcess', [$dto]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract::getDb

     * @throws Exception
     */
    public function testGetDb(): void
    {
        $this->nullConnector->setApplication(new TestNullApplication());
        self::assertNotEmpty($this->nullConnector->getApplication());
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->nullConnector = new TestNullCustomNode();
    }

}
