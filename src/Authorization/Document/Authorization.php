<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 2:01 PM
 */

namespace Hanaboso\PipesFramework\Authorization\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;

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
     * @var string[]
     */
    private $token = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $encryptedToken = '';

    /**
     * @var string[]
     */
    private $settings = [];

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $encryptedSettings = '';

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
    public function setToken($token): Authorization
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
     * @return string
     */
    public function getEncryptedToken(): string
    {
        return $this->encryptedToken;
    }

    /**
     * @param string $encryptedToken
     */
    public function setEncryptedToken(string $encryptedToken): void
    {
        $this->encryptedToken = $encryptedToken;
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
     * @return string
     */
    public function getEncryptedSettings(): string
    {
        return $this->encryptedSettings;
    }

    /**
     * @param string $encryptedSettings
     *
     * @return Authorization
     */
    public function setEncryptedSettings(string $encryptedSettings): Authorization
    {
        $this->encryptedSettings = $encryptedSettings;

        return $this;
    }

    /**
     * @ODM\PreFlush
     */
    public function preFlushEncrypt(): void {
        $this->encryptedToken = CryptManager::encrypt($this->token);
        $this->encryptedSettings = CryptManager::encrypt($this->settings);
    }

    /**
     * @ODM\PostLoad
     */
    public function postLoadEncrypt(): void {
        $this->token = CryptManager::decrypt($this->encryptedToken);
        $this->settings = CryptManager::decrypt($this->encryptedSettings);
    }

}