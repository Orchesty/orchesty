<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\System\PipesHeaders;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class RefreshOAuth2TokenCustomNodeTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector
 */
final class RefreshOAuth2TokenCustomNodeTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testProcess(): void
    {
        $providerMock = self::createMock(OAuth2Provider::class);
        $providerMock->method('refreshAccessToken')->willReturn(
            [
                'code'         => 'code123',
                'access_token' => 'token333',
            ]
        );

        self::$container->set('hbpf.providers.oauth2_provider', $providerMock);
        $connector          = self::$container->get('hbpf.custom_node.refresh_oauth2_token');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            'mailchimp',
            'user',
            'fa830d8d43*****bac307906e83de659'
        );

        $applicationInstall->setExpires(DateTimeUtils::getUtcDateTime('+1 hour'));
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_SETTINGS => [
                    ApplicationInterface::TOKEN => [
                        'code'          => 'code123',
                        'token'         => 'fa830d8d43*****bac307906e83de659',
                        'refresh_token' => 'refresh_token22',
                    ],
                ],
            ]
        );
        $this->pfd($applicationInstall);
        $this->dm->clear();
        $dto = DataProvider::getProcessDto('mailchimp', 'user', '');
        $dto->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::USER)                                     => ['user'],
                PipesHeaders::createKey(PipesHeaders::APPLICATION)                              => ['mailchimp'],
                PipesHeaders::createKey(GetApplicationForRefreshBatchConnector::APPLICATION_ID) => [
                    $applicationInstall->getId(),
                ],
            ]
        );

        $response = $connector->process($dto);
        self::assertEquals($response->getData(), '');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\RefreshOAuth2TokenNode::getId
     */
    public function testGetId(): void
    {
        $application = self::$container->get('hbpf.custom_node.refresh_oauth2_token');

        self::assertEquals('refresh_oauth2_token', $application->getId());
    }

}
