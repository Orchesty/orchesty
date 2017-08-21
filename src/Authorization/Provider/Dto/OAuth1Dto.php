<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 17.8.17
 * Time: 15:31
 */

namespace Hanaboso\PipesFramework\Authorization\Provider\Dto;

use Hanaboso\PipesFramework\Authorization\Document\Authorization;

/**
 * Class OAuth1Dto
 *
 * @package Hanaboso\PipesFramework\Authorization\Provider\Dto
 */
final class OAuth1Dto
{

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var string
     */
    private $consumerKey;

    /**
     * @var string
     */
    private $consumerSecret;

    /**
     * @var string
     */
    private $signatureMethod;

    /**
     * @var int
     */
    private $authType;

    /**
     * OAuth1Dto constructor.
     *
     * @param Authorization $authorization
     * @param string        $consumerKey
     * @param string        $consumerSecret
     * @param string        $signatureMethod
     * @param int           $authType
     */
    public function __construct(
        Authorization $authorization,
        string $consumerKey,
        string $consumerSecret,
        string $signatureMethod = OAUTH_SIG_METHOD_HMACSHA1,
        int $authType = OAUTH_AUTH_TYPE_AUTHORIZATION
    )
    {
        $this->authorization   = $authorization;
        $this->consumerKey     = $consumerKey;
        $this->consumerSecret  = $consumerSecret;
        $this->signatureMethod = $signatureMethod;
        $this->authType        = $authType;
    }

    /**
     * @return string
     */
    public function getConsumerKey(): string
    {
        return $this->consumerKey;
    }

    /**
     * @return string
     */
    public function getConsumerSecret(): string
    {
        return $this->consumerSecret;
    }

    /**
     * @return string
     */
    public function getSignatureMethod(): string
    {
        return $this->signatureMethod;
    }

    /**
     * @return int
     */
    public function getAuthType(): int
    {
        return $this->authType;
    }

    /**
     * @return Authorization
     */
    public function getAuthorization(): Authorization
    {
        return $this->authorization;
    }

}