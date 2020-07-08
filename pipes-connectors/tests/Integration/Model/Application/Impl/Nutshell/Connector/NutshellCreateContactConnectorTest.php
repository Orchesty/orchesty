<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector\NutshellCreateContactConnector;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\PipesHeaders;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use ReflectionException;

/**
 * Class NutshellCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell\Connector
 *
 * @covers  \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector\NutshellCreateContactConnector
 */
final class NutshellCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var NutshellCreateContactConnector
     */
    private $connector;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\Connector\NutshellCreateContactConnector::getId
     */
    public function testGetId(): void
    {
        self::assertEquals('nutshell-create-contact', $this->connector->getId());
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
        $data = (string) file_get_contents(__DIR__ . '/Data/newContact.json');
        $this->mockSender($data);
        $applicationInstall = DataProvider::getBasicAppInstall('nutshell');
        $this->pfd($applicationInstall);

        $dto    = (new ProcessDto())->setData($data)->setHeaders(
            [
                PipesHeaders::createKey('application') => 'nutshell',
                PipesHeaders::createKey('user')        => 'user',
            ]
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

        $this->connector = self::$container->get('hbpf.connector.nutshell-create-contact');
    }

    /**
     * @param string $data
     *
     * @throws ReflectionException
     */
    private function mockSender(string $data): void
    {
        $sender = self::createPartialMock(CurlManager::class, ['send']);
        $sender->expects(self::any())->method('send')->willReturn(
            new ResponseDto(200, 'success', $data, [])
        );
        $this->setProperty($this->connector, 'curlManager', $sender);
    }

}
