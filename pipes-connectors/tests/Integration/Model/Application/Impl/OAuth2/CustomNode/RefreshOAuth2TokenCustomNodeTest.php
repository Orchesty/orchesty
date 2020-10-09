<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\CustomNode;

use Exception;
use GuzzleHttp\Promise\Promise;
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
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\CustomNode
 */
final class RefreshOAuth2TokenCustomNodeTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testProcess(): void
    {

        $callback = static function (): void {
            self::assertFake();
        };

        $providerMock = self::createMock(OAuth2Provider::class);
        $providerMock->method('refreshAccessToken')->willReturn(
            [
                'code'         => 'code123',
                'access_token' => 'token333',
            ]
        );

        self::$container->set('hbpf.providers.oauth2_provider', $providerMock);
        $application        = self::$container->get('hbpf.connector.batch-refresh_oauth2_token');
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
        $dto = DataProvider::getProcessDto('mailchimp', 'user', '{"body":"body"}');
        $dto->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::USER)                                     => ['user'],
                PipesHeaders::createKey(PipesHeaders::APPLICATION)                              => ['mailchimp'],
                PipesHeaders::createKey(GetApplicationForRefreshBatchConnector::APPLICATION_ID) => [
                    $applicationInstall->getId(),
                ],
            ]
        );

        $response = $application->processBatch($dto, $callback);
        $response->then(static function ($arg): void {
            self::assertEquals('{"body":"body"}', $arg);
        });
        self::assertEquals(Promise::class, get_class($response));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\RefreshOAuth2TokenBatchConnector::getId
     */
    public function testGetId(): void
    {
        $application = self::$container->get('hbpf.connector.batch-refresh_oauth2_token');

        self::assertEquals('refresh_oauth2_token', $application->getId());
    }

}
