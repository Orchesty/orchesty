<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\ControllerTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class IDokladApplicationTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad
 */
final class IDokladApplicationTest extends ControllerTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @throws Exception
     */
    #[Group('live')]
    public function testAuthorize(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $app                = self::getContainer()->get('hbpf.application.i-doklad');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'token123',
            'ae89f69a-44f4-4163-ac98-************',
            'de469040-fc97-4e03-861e-************',
        );

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["flexibee"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($applicationInstall->toArray())),
            ),
        );

        $url = $app->authorize($applicationInstall);
        parse_str($url, $output);
        self::assertArrayHasKey('approval_prompt', $output);
        self::assertArrayHasKey('redirect_uri', $output);
        self::assertTrue(str_contains($output['redirect_uri'], '/api/applications/authorize/token'));
        self::assertArrayHasKey('client_id', $output);
        self::assertArrayHasKey('scope', $output);
        self::assertTrue(
            str_contains($output['scope'], 'idoklad_api') && str_contains($output['scope'], 'offline_access'),
        );
        self::assertArrayHasKey('state', $output);
        self::assertArrayHasKey('access_type', $output);
    }

    /**
     * @throws Exception
     */
    #[Group('live')]
    public function testCreateAccessToken(): void
    {
        self::markTestSkipped('live tests');
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $app = self::getContainer()->get('hbpf.application.i-doklad');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            '',
            'ae89f69a-44f4-4163-ac98-************',
            'de469040-fc97-4e03-861e-************',
        );

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["i-doklad"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($applicationInstall->toArray())),
            ),
        );

        $app->setFrontendRedirectUrl($applicationInstall, 'https://127.0.0.11');

        $uri = '/applications/authorize/token?code=b9bd3cdb40745683c7bfc284d747b45a&state=dXNlcjppLWRva2xhZA%2C%2C';
        $this->client->request('GET', $uri);
        $res = $this->client->getResponse();

        self::assertEquals(200, $res->getStatusCode());
        /** @var ApplicationInstall $doc */
        $doc      = self::getContainer()->get('hbpf.application_install.repository')->findById(
            $applicationInstall->getId() ?? '',
        );
        $settings = $doc->getSettings();
        self::assertArrayHasKey(ApplicationInterface::AUTHORIZATION_FORM, $settings);
        self::assertArrayHasKey(ApplicationInterface::TOKEN, $settings[ApplicationInterface::AUTHORIZATION_FORM]);
        self::assertArrayHasKey(
            OAuth2Provider::ACCESS_TOKEN,
            $settings[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN],
        );
    }

}
