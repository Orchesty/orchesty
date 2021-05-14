<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use HbPFConnectorsTests\ControllerTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class IDokladApplicationTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\IDoklad
 */
final class IDokladApplicationTest extends ControllerTestCaseAbstract
{

    /**
     * @group live
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $app                = self::$container->get('hbpf.application.i-doklad');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'token123',
            'ae89f69a-44f4-4163-ac98-************',
            'de469040-fc97-4e03-861e-************',
        );
        $this->pfd($applicationInstall);
        $url = $app->authorize($applicationInstall);
        parse_str($url, $output);
        self::assertArrayHasKey('approval_prompt', $output);
        self::assertArrayHasKey('redirect_uri', $output);
        self::assertTrue(str_contains($output['redirect_uri'], '/api/applications/authorize/token'));
        self::assertArrayHasKey('client_id', $output);
        self::assertArrayHasKey('scope', $output);
        self::assertTrue(
            str_contains($output['scope'], 'idoklad-api') && str_contains($output['scope'], 'offline_access'),
        );
        self::assertArrayHasKey('state', $output);
        self::assertArrayHasKey('access_type', $output);
    }

    /**
     * @group live
     * @throws Exception
     */
    public function testCreateAccessToken(): void
    {
        $app = self::$container->get('hbpf.application.i-doklad');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            '',
            'ae89f69a-44f4-4163-ac98-************',
            'de469040-fc97-4e03-861e-************',
        );
        $app->setFrontendRedirectUrl($applicationInstall, 'https://127.0.0.11');
        $this->pfd($applicationInstall);

        $uri = '/applications/authorize/token?code=b9bd3cdb40745683c7bfc284d747b45a&state=dXNlcjppLWRva2xhZA%2C%2C';
        $this->client->request('GET', $uri);
        $res = $this->client->getResponse();
        self::assertEquals(200, $res->getStatusCode());
        /** @var ApplicationInstall $doc */
        $doc      = $this->dm->getRepository(ApplicationInstall::class)->find($applicationInstall->getId());
        $settings = $doc->getSettings();
        self::assertArrayHasKey(BasicApplicationInterface::AUTHORIZATION_SETTINGS, $settings);
        self::assertArrayHasKey(
            BasicApplicationInterface::TOKEN,
            $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS],
        );
        self::assertArrayHasKey(
            OAuth2Provider::ACCESS_TOKEN,
            $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::TOKEN],
        );
    }

}
