<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\RefreshOAuth2TokenNode;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class RefreshOAuth2TokenCustomNodeTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector
 */
final class RefreshOAuth2TokenCustomNodeTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testProcess(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"ids":[""],"deleted":null}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([])),
            ),
        );

        $providerMock = self::createMock(OAuth2Provider::class);
        $providerMock->method('refreshAccessToken')->willReturn(
            [
                'access_token' => 'token333',
                'code'         => 'code123',
            ],
        );

        self::getContainer()->set('hbpf.providers.oauth2_provider', $providerMock);
        /** @var RefreshOAuth2TokenNode $connector */
        $connector          = self::getContainer()->get('hbpf.custom_node.refresh_oauth2_token');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            'mailchimp',
            'user',
            'fa830d8d43*****bac307906e83de659',
        );

        $applicationInstall->setExpires(DateTimeUtils::getUtcDateTime('+1 hour'));
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    ApplicationInterface::TOKEN => [
                        'code'          => 'code123',
                        'refresh_token' => 'refresh_token22',
                        'token'         => 'fa830d8d43*****bac307906e83de659',
                    ],
                ],
            ],
        );
        $dto = DataProvider::getProcessDto('mailchimp', 'user', '');
        $dto->setHeaders(
            [
                GetApplicationForRefreshBatchConnector::NAME => $applicationInstall->getId(),
                PipesHeaders::APPLICATION                    => 'mailchimp',
                PipesHeaders::USER                           => 'user',
            ],
        );

        $response = $connector->processAction($dto);
        self::assertEquals('', $response->getData());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\RefreshOAuth2TokenNode::getName
     */
    public function testGetName(): void
    {
        $application = self::getContainer()->get('hbpf.custom_node.refresh_oauth2_token');

        self::assertEquals('refresh_oauth2_token', $application->getName());
    }

}
