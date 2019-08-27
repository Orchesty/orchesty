<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Hubspot\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubspotCreateContactConnector;
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
     * @param int  $code
     * @param bool $isValid
     *
     * @throws Exception
     *
     * @dataProvider getDataProvider
     */
    public function testProcessAction(int $code, bool $isValid): void
    {
        $this->mockCurl([
            new MockCurlMethod(
                $code,
                sprintf('response%s.json', $code),
                []
            ),
        ]);

        $app                           = self::$container->get('hbpf.application.hubspot');
        $hubspotCreateContactConnector = new HubspotCreateContactConnector(
            $app,
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $this->pf(DataProvider::getOauth2AppInstall($app->getKey()));
        $response = $hubspotCreateContactConnector->processAction(DataProvider::getProcessDto($app->getKey()));

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code),
                );
        } else{
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code),
                );
        }
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [409, FALSE],
            [400, FALSE],
            [200, TRUE],
        ];
    }

}
