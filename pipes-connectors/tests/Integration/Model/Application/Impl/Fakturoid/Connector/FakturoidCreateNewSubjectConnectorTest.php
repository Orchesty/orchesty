<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewSubjectConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class FakturoidCreateNewSubjectConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewSubjectConnectorTest extends FakturoidAbstractTestConnector
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewSubjectConnector::getName
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals(
            'fakturoid.create-new-subject',
            $this->createConnector(DataProvider::createResponseDto())->getName(),
        );
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["fakturoid"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->getApplication('fakturacnitest')->toArray()])),
            ),
        );

        $app          = self::getContainer()->get('hbpf.application.fakturoid');
        $dataFromFile = File::getContent(__DIR__ . '/Data/requestCreateNewSubject.json');
        $dto          = DataProvider::getProcessDto(
            $app->getName(),
            'user',
            $dataFromFile,
        );

        $res = $this->createConnector(
            DataProvider::createResponseDto($dataFromFile),
        )
            ->setApplication($app)
            ->processAction($dto);
        self::assertEquals($dataFromFile, $res->getData());
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return FakturoidCreateNewSubjectConnector
     */
    public function createConnector(ResponseDto $dto, ?Exception $exception = NULL): FakturoidCreateNewSubjectConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        $fakturoidCreateNewSubjectConnector = new FakturoidCreateNewSubjectConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $fakturoidCreateNewSubjectConnector
            ->setSender($sender);

        return $fakturoidCreateNewSubjectConnector;
    }

    /**
     * @return FakturoidCreateNewSubjectConnector
     */
    public function setApplication(): FakturoidCreateNewSubjectConnector
    {
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $fakturoidConnector = new FakturoidCreateNewSubjectConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $fakturoidConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        return $fakturoidConnector;
    }

}
