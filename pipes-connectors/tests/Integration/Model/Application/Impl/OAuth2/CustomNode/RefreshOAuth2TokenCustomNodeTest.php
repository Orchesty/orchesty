<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\OAuth2\CustomNode;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode\GetApplicationForRefreshBatch;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class RefreshOAuth2TokenCustomNodeTest
 *
 * @package Tests\Integration\Model\Application\Impl\OAuth2\CustomNode
 */
class RefreshOAuth2TokenCustomNodeTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws DateTimeException
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
        $application        = self::$container->get('hbpf.custom_node.refresh_oauth2_token');
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
        $this->pf($applicationInstall);
        $this->dm->clear();
        $dto = DataProvider::getProcessDto(
            'mailchimp',
            'user',
            '{"body":"body"}'
        );
        $dto->setHeaders(
            [
                PipesHeaders::createKey(PipesHeaders::USER)                            => ['user'],
                PipesHeaders::createKey(PipesHeaders::APPLICATION)                     => ['mailchimp'],
                PipesHeaders::createKey(GetApplicationForRefreshBatch::APPLICATION_ID) => [
                    $applicationInstall->getId(),
                ],
            ]
        );

        $response = $application->process($dto);
        $this->assertEquals('{"body":"body"}', $response->getData());
        $this->assertEquals(ProcessDto::class, get_class($response));
    }

}
