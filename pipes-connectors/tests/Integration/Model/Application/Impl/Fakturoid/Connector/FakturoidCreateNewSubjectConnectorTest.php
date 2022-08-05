<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewSubjectConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FakturoidCreateNewSubjectConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewSubjectConnectorTest extends FakturoidAbstractConnectorTest
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
        $this->setApplicationAndMock('fakturacnitest');

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

        $fakturoidCreateNewSubjectConnector = new FakturoidCreateNewSubjectConnector();
        $fakturoidCreateNewSubjectConnector
            ->setSender($sender)
            ->setDb($this->dm);

        return $fakturoidCreateNewSubjectConnector;
    }

    /**
     * @return FakturoidCreateNewSubjectConnector
     */
    public function setApplication(): FakturoidCreateNewSubjectConnector
    {
        $app                = self::getContainer()->get('hbpf.application.fakturoid');
        $fakturoidConnector = new FakturoidCreateNewSubjectConnector();
        $fakturoidConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setDb($this->dm)
            ->setApplication($app);

        return $fakturoidConnector;
    }

}
