<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Hubspot\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubspotCreateContactConnector;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class HubspotCreateContactConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Hubspot\Connector
 */
final class HubspotCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @var HubspotCreateContactConnector
     */
    private $connector;

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws Exception
     *
     * @dataProvider getDataProvider
     */
    public function testProcessAction(int $code, bool $isValid): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    []
                ),
            ]
        );

        $this->setConnector();
        $app = self::$container->get('hbpf.application.hubspot');
        $this->connector->setApplication($app);

        $this->pf(DataProvider::getOauth2AppInstall($app->getKey()));
        $response = $this->connector->processAction(DataProvider::getProcessDto($app->getKey()));

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        }
    }

    /**
     * @return mixed[]
     */
    public function getDataProvider(): array
    {
        return [
            [409, FALSE],
            [400, FALSE],
            [200, TRUE],
        ];
    }

    /**
     *
     */
    public function testGetId(): void
    {
        $this->setConnector();
        $this->assertEquals('hubspot_create_contact', $this->connector->getId());
    }

    /**
     * @throws ConnectorException
     */
    public function testProcessEvent(): void
    {
        $this->setConnector();
        self::expectException(ConnectorException::class);
        $this->connector->processEvent(
            DataProvider::getProcessDto(
                'hubspot',
                'user',
                ''
            )
        );
    }

    /**
     *
     */
    private function setConnector(): void
    {
        $this->connector = new HubspotCreateContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );
    }

}
