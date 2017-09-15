<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 14:35
 */

namespace Hanaboso\PipesFramework\Authorization\Impl\Magento2;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorization\Base\OAuthAuthorizationAbstract;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth1Provider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Magento2OAuthAuthorization
 *
 * @package Hanaboso\PipesFramework\Authorization\Impl\Magento2
 */
class Magento2OAuthAuthorization extends OAuthAuthorizationAbstract implements Magento2AuthorizationInterface, LoggerAwareInterface
{

    private const URL             = 'url';
    private const CONSUMER_KEY    = 'consumer_key';
    private const CONSUMER_SECRET = 'consumer_secret';

    /**
     * @var OAuth1Provider
     */
    private $auth1Provider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Magento2OAuthAuthorization constructor.
     *
     * @param DocumentManager $dm
     * @param OAuth1Provider  $auth1Provider
     * @param string          $id
     * @param string          $name
     * @param string          $description
     */
    public function __construct(
        DocumentManager $dm,
        OAuth1Provider $auth1Provider,
        string $id,
        string $name,
        string $description
    )
    {
        parent::__construct($id, $name, $description, $dm);
        $this->auth1Provider = $auth1Provider;
        $this->logger        = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return Magento2OAuthAuthorization
     */
    public function setLogger(LoggerInterface $logger): Magento2OAuthAuthorization
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::OAUTH;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        if (!$this->authorization) {
            return FALSE;
        }
        $token = $this->authorization->getToken();

        return !empty($token[OAuth1Provider::OAUTH_TOKEN] ?? '') && !empty($token[OAuth1Provider::OAUTH_TOKEN_SECRET] ?? '');
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getHeaders(string $method, string $url): array
    {
        if (!$this->isAuthorized()) {
            $this->logger->error('Magento2 OAuth not authorized');
            throw new AuthorizationException('Magento2 OAuth not authorized');
        }

        return [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => $this->auth1Provider->getAuthorizeHeader($this->buildDto(), $method, $url),
        ];
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->getSettings()[self::URL];
    }

    /**
     * @return string[]
     *
     * @throws AuthorizationException
     */
    public function getSettings(): array
    {
        $this->loadAuthorization();
        if (!$this->authorization) {
            throw new AuthorizationException(
                sprintf('Authorization settings \'%s\' not found', $this->getId()),
                AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND
            );
        }

        $settings = $this->authorization->getSettings();
        if (empty($settings[self::URL]) || empty($settings[self::CONSUMER_KEY]) || empty($settings[self::CONSUMER_SECRET])) {
            throw new AuthorizationException(
                sprintf('Authorization settings \'%s\' not found', $this->getId()),
                AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND
            );
        }

        $settings['readme'] = $this->getReadMe();

        return $settings;
    }

    /**
     * @param string[] $data
     *
     * @throws AuthorizationException
     */
    public function saveSettings(array $data): void
    {
        $this->loadAuthorization();
        if (!$this->authorization) {
            $this->authorization = new Authorization($this->getId());
            $this->dm->persist($this->authorization);
        }

        $this->authorization->setSettings([
            self::URL             => $data['field1'],
            self::CONSUMER_KEY    => $data['field2'],
            self::CONSUMER_SECRET => $data['field3'],
        ]);
        $this->dm->flush();
    }

    /**
     * @param string[] $data
     */
    public function saveToken(array $data): void
    {
        $this->loadAuthorization();
        if (!$this->authorization) {
            $this->authorization = new Authorization($this->getId());
            $this->dm->persist($this->authorization);
        }

        $token = $this->auth1Provider->getAccessToken($this->buildDto(), $data, $this->getAccessTokenUrl());
        $this->authorization->setToken($token);
        $this->dm->flush();
    }

    /**
     * @param string $hostname
     *
     * @return array
     */
    public function getInfo(string $hostname): array
    {
        $info                 = parent::getInfo($hostname);
        $info['redirect_url'] = sprintf('%s/api/authorizations/%s/save_token', $hostname, $this->getId());

        return $info;
    }

    /**
     *
     */
    public function authorize(): void
    {
        $this->loadAuthorization();
        $this->auth1Provider->authorize(
            $this->buildDto(),
            $this->getRequestTokenUrl(),
            $this->getAuthorizationUrl(),
            []
        );
    }

    /**
     * @return string
     */
    public function getReadMe(): string
    {
        return 'Field1 contains connector URL, field2 contains consumer key, field3 contains consumer secret.';
    }

    /**
     *
     */
    private function loadAuthorization(): void
    {
        $this->authorization = $this->dm->getRepository(Authorization::class)->findOneBy([
            'authorizationKey' => $this->getId(),
        ]);
    }

    /**
     * @return OAuth1Dto
     */
    private function buildDto(): OAuth1Dto
    {
        $settings = $this->getSettings();

        return new OAuth1Dto($this->authorization, $settings[self::CONSUMER_KEY], $settings[self::CONSUMER_SECRET]);
    }

    /**
     * @return string
     */
    private function getAuthorizationUrl(): string
    {
        return sprintf('%s/admin/oauth_authorize', $this->getSettings()[self::URL]);
    }

    /**
     * @return string
     */
    private function getAccessTokenUrl(): string
    {
        return sprintf('%s/oauth/token', $this->getSettings()[self::URL]);
    }

    /**
     * @return string
     */
    private function getRequestTokenUrl(): string
    {
        return sprintf('%s/oauth/initiate', $this->getSettings()[self::URL]);
    }

}