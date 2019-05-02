<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Provider\Dto;

use Hanaboso\PipesFramework\Application\Base\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Class OAuth1Dto
 *
 * @package Hanaboso\PipesFramework\Authorization\Provider\Dto
 */
final class OAuth1Dto implements OAuth1DtoInterface
{

    /**
     * @var ApplicationInstall
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
     * @param ApplicationInstall $authorization
     * @param string             $consumerKey
     * @param string             $consumerSecret
     * @param string             $signatureMethod
     * @param int                $authType
     */
    public function __construct(
        ApplicationInstall $authorization,
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
     * @return ApplicationInstall
     */
    public function getAuthorization(): ApplicationInstall
    {
        return $this->authorization;
    }

    /**
     * @return array
     */
    public function getToken(): array
    {
        return $this->authorization->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::TOKEN];
    }

}
