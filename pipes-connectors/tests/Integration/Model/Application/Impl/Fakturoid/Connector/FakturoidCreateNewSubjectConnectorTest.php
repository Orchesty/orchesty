<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewSubjectConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FakturoidCreateNewSubjectConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewSubjectConnectorTest extends FakturoidAbstractConnectorTest
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewSubjectConnector::getId
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidCreateNewSubjectConnector::__construct
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals(
            'fakturoid.create-new-subject',
            $this->createConnector(DataProvider::createResponseDto())->getId(),
        );
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->setApplicationAndMock('fakturacnitest');

        $app          = self::$container->get('hbpf.application.fakturoid');
        $dataFromFile = (string) file_get_contents(__DIR__ . '/Data/requestCreateNewSubject.json');
        $dto          = DataProvider::getProcessDto(
            $app->getKey(),
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

        return new FakturoidCreateNewSubjectConnector($sender, $this->dm);
    }

    /**
     * @return FakturoidCreateNewSubjectConnector
     */
    public function setApplication(): FakturoidCreateNewSubjectConnector
    {
        $app                = self::$container->get('hbpf.application.fakturoid');
        $fakturoidConnector = new FakturoidCreateNewSubjectConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm,
        );

        $fakturoidConnector->setApplication($app);

        return $fakturoidConnector;
    }

}
