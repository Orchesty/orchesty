<?php declare(strict_types=1);

namespace Tests;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;

/**
 * Class DataProvider
 *
 * @package Tests
 */
final class DataProvider
{

    /**
     * @param string $key
     * @param string $user
     * @param string $accessToken
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return ApplicationInstall
     * @throws DateTimeException
     */
    public static function getOauth2AppInstall(
        string $key,
        string $user = 'user',
        string $accessToken = 'token123',
        string $clientId = 'clientId',
        string $clientSecret = 'clientSecret'
    ): ApplicationInstall
    {
        $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN] = $accessToken;
        $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][OAuth2ApplicationInterface::CLIENT_ID]                     = $clientId;
        $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][OAuth2ApplicationInterface::CLIENT_SECRET]                 = $clientSecret;
        $applicationInstall = new ApplicationInstall();

        return $applicationInstall
            ->setSettings($settings)
            ->setUser($user)
            ->setKey($key);
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $body
     *
     * @return ProcessDto
     */
    public static function getProcessDto(string $key, string $user = 'user', string $body = ''): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setData($body)
            ->setHeaders(['pf-user' => $user, 'pf-key' => $key]);

        return $dto;
    }

}
