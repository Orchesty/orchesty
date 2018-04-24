<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 2:01 PM
 */

namespace Hanaboso\PipesFramework\Authorization\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;

/**
 * Class Authorization
 *
 * @package Hanaboso\PipesFramework\Authorization\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Authorization\Repository\AuthorizationRepository")
 *
 * @ODM\HasLifecycleCallbacks()
 */
class Authorization
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $authorizationKey;

    /**
     * @var array
     */
    private $token = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $encryptedToken;

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $encryptedSettings;

    /**
     * Authorization constructor.
     *
     * @param string $id
     */
    function __construct(string $id)
    {
        $this->authorizationKey = $id;
    }

    /**
     * @return string[]
     */
    public function getToken(): array
    {
        return $this->token;
    }

    /**
     * @param string[] $token
     *
     * @return Authorization
     */
    public function setToken(array $token): Authorization
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorizationKey(): string
    {
        return $this->authorizationKey;
    }

    /**
     * @param string $authorizationKey
     *
     * @return Authorization
     */
    public function setAuthorizationKey(string $authorizationKey): Authorization
    {
        $this->authorizationKey = $authorizationKey;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param string[] $settings
     *
     * @return Authorization
     */
    public function setSettings(array $settings): Authorization
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @ODM\PreFlush
     */
    public function preFlushEncrypt(): void
    {
        $this->encryptedToken    = CryptManager::encrypt($this->token);
        $this->encryptedSettings = CryptManager::encrypt($this->settings);
    }

    /**
     * @ODM\PostLoad
     */
    public function postLoadDecrypt(): void
    {
        $this->token    = CryptManager::decrypt($this->encryptedToken);
        $this->settings = CryptManager::decrypt($this->encryptedSettings);
    }

}