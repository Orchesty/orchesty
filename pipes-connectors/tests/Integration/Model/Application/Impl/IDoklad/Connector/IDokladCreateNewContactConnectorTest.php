<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector\IDokladCreateNewContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class IDokladCreateNewContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\IDoklad\Connector
 */
final class IDokladCreateNewContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var IDokladApplication
     */
    private IDokladApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector\IDokladCreateNewContactConnector::getId
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector\IDokladCreateNewContactConnector::__construct
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals(
            'i-doklad.create-new-contact',
            $this->createConnector(DataProvider::createResponseDto())->getId(),
        );
    }

    /**
     * @throws ConnectorException
     */
    public function testProcessEvent(): void
    {
        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
        $this->createConnector(DataProvider::createResponseDto())->processEvent(DataProvider::getProcessDto());
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getKey()));
        $this->dm->clear();

        $dataFromFile = (string) file_get_contents(__DIR__ . '/newContact.json');

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            $dataFromFile,
        );

        $res = $this->createConnector(
            DataProvider::createResponseDto($dataFromFile),
        )
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals($dataFromFile, $res->getData());
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessActionRequestException(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getKey()));
        $this->dm->clear();

        $dataFromFile = (string) file_get_contents(__DIR__ . '/newContact.json');

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            $dataFromFile,
        );

        self::expectException(OnRepeatException::class);
        $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->app)
            ->processAction($dto);
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessActionRequestLogicException(): void
    {
        $this->pfd(DataProvider::getOauth2AppInstall($this->app->getKey()));
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->app->getKey(),
            'user',
            '{
            "BankId": 1
            }',
        );

        $this->createConnector(
            DataProvider::createResponseDto(
                '{
            "BankId": 1
            }',
            ),
        )
            ->setApplication($this->app)
            ->processAction($dto);
        self::assertEquals('1003', $dto->getHeaders()['pf-result-code']);
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new IDokladApplication(self::$container->get('hbpf.providers.oauth2_provider'));
    }

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return IDokladCreateNewContactConnector
     */
    private function createConnector(ResponseDto $dto, ?Exception $exception = NULL): IDokladCreateNewContactConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        return new IDokladCreateNewContactConnector($this->dm, $sender);
    }

}
