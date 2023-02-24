<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector\NutshellCreateContactConnector;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockServer\Mock;
use HbPFConnectorsTests\MockServer\MockServer;
use ReflectionException;

/**
 * Class NutshellCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell\Connector
 *
 * @covers  \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector\NutshellCreateContactConnector
 */
final class NutshellCreateContactConnectorTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var NutshellCreateContactConnector
     */
    private NutshellCreateContactConnector $connector;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector\NutshellCreateContactConnector::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('nutshell-create-contact', $this->connector->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector\NutshellCreateContactConnector::processAction
     *
     * @throws ReflectionException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws PipesFrameworkException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $data = File::getContent(__DIR__ . '/Data/newContact.json');
        $this->mockSender($data);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["nutshell"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode([DataProvider::getBasicAppInstall('nutshell')->toArray()]),
                ),
            ),
        );

        $dto    = (new ProcessDto())->setData($data)->setHeaders(
            [
                'application' => 'nutshell',
                'user'        => 'user',
            ],
        );
        $result = $this->connector->processAction($dto);

        self::assertEquals($data, $result->getData());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->connector = self::getContainer()->get('hbpf.connector.nutshell-create-contact');
    }

    /**
     * @param string $data
     */
    private function mockSender(string $data): void
    {
        $sender = self::createPartialMock(CurlManager::class, ['send']);
        $sender->expects(self::any())->method('send')->willReturn(
            new ResponseDto(200, 'success', $data, []),
        );
        $this->setProperty($this->connector, 'sender', $sender);
    }

}
