<?php declare(strict_types=1);

namespace Tests;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
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
     * @throws Exception
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
     * @param string $password
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public static function getBasicAppInstall(
        string $key,
        string $user = 'user',
        string $password = 'pass123'
    ): ApplicationInstall
    {
        $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationAbstract::USER]     = $user;
        $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationAbstract::PASSWORD] = $password;

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
            ->setHeaders(
                [
                    PipesHeaders::createKey(PipesHeaders::USER)        => [$user],
                    PipesHeaders::createKey(PipesHeaders::APPLICATION) => [$key],
                ]
            );

        return $dto;
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $settings
     * @param mixed[] $nonEncryptedSettings
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    public static function createApplicationInstall(
        string $key,
        string $user = 'user',
        array $settings = [],
        array $nonEncryptedSettings = []
    ): ApplicationInstall
    {
        return (new ApplicationInstall())
            ->setKey($key)
            ->setUser($user)
            ->setSettings($settings)
            ->setNonEncryptedSettings($nonEncryptedSettings);
    }

}
