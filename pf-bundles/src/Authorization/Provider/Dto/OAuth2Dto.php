<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.9.17
 * Time: 8:15
 */

namespace Hanaboso\PipesFramework\Authorization\Provider\Dto;

/**
 * Class OAuth2Dto
 *
 * @package Hanaboso\PipesFramework\Authorization\Provider\Dto
 */
class OAuth2Dto implements OAuth2DtoInterface
{

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $redirectUrl;

    /**
     * @var string
     */
    private $authorizeUrl;

    /**
     * @var string
     */
    private $tokenUrl;

    /**
     * OAuth2Dto constructor.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUrl
     * @param string $authorizeUrl
     * @param string $tokenUrl
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUrl,
        string $authorizeUrl,
        string $tokenUrl
    )
    {

        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUrl  = $redirectUrl;
        $this->authorizeUrl = $authorizeUrl;
        $this->tokenUrl     = $tokenUrl;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @return string
     */
    public function getAuthorizeUrl(): string
    {
        return $this->authorizeUrl;
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

}