<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2DtoInterface;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class OAuth2ApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
abstract class OAuth2ApplicationAbstract extends OAuth1ApplicationAbstract implements OAuth2ApplicationInterface
{

    /**
     * @var OAuth2Provider
     */
    private $OAuth2Provider;

    /**
     * @var OAuth2DtoInterface
     */
    private $dto;

    /**
     * OAuth2ApplicationAbstract constructor.
     *
     * @param OAuth2Provider $provider2
     */
    public function __construct(OAuth2Provider $provider2)
    {
        parent::__construct($this->OAuth1Provider);
        $this->OAuth2Provider = $provider2;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return BasicApplicationInterface::OAUTH2;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     */
    public function authorize(ApplicationInstall $applicationInstall): void
    {
        $this->OAuth2Provider->authorize($this->dto);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return ApplicationInstall
     * @throws AuthorizationException
     */
    public function refreshAuthorization(ApplicationInstall $applicationInstall): ApplicationInstall
    {
        $accessToken = $this->OAuth2Provider->refreshAccessToken($this->dto, $this->getTokens());

        return $applicationInstall->setSettings([
            BasicApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::TOKEN => $accessToken],
        ]);

    }

    /**
     * @return array
     */
    abstract protected function getTokens(): array;

}