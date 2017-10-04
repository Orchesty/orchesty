<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:52 PM
 */

namespace CcApi\ApiEntity;

/**
 * Class UserSystem
 *
 * @package CcApi\Entity
 */
class UserSystem extends System
{

    /**
     * @var string
     */
    private $token;

    /**
     * @var bool
     */
    private $authorized = FALSE;

    /**
     * @var bool
     */
    private $synchronized = FALSE;

    /**
     * @var array
     */
    private $settingFields = [];

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * @param bool $authorized
     *
     * @return $this
     */
    public function setAuthorized(bool $authorized): self
    {
        $this->authorized = $authorized;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSynchronized(): bool
    {
        return $this->synchronized;
    }

    /**
     * @param bool $synchronized
     *
     * @return $this
     */
    public function setSynchronized(bool $synchronized): self
    {
        $this->synchronized = $synchronized;

        return $this;
    }

    /**
     * @return array|SettingField[]
     */
    public function getSettingFields(): array
    {
        return $this->settingFields;
    }

    /**
     * @param SettingField $settingField
     *
     * @return $this
     */
    public function addSettingField(SettingField $settingField): self
    {
        $this->settingFields[] = $settingField;

        return $this;
    }

}