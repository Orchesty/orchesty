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
     * @var array
     */
    private $customForm = [];

    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var array|DataLayout[]
     */
    private $dataLayouts = [];

    /**
     * @var array|MapTemplate[]
     */
    private $mapTemplates = [];

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

    /**
     * @return array
     */
    public function getCustomForm(): array
    {
        return $this->customForm;
    }

    /**
     * @param array $customForm
     *
     * @return UserSystem
     */
    public function setCustomForm(array $customForm): UserSystem
    {
        $this->customForm = $customForm;

        return $this;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     *
     * @return UserSystem
     */
    public function setActions(array $actions): UserSystem
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @return array|DataLayout[]
     */
    public function getDataLayouts(): array
    {
        return $this->dataLayouts;
    }

    /**
     * @param DataLayout $dataLayout
     *
     * @return UserSystem
     */
    public function addDataLayout(DataLayout $dataLayout): UserSystem
    {
        $this->dataLayouts[] = $dataLayout;

        return $this;
    }

    /**
     * @return array|MapTemplate[]
     */
    public function getMapTemplates(): array
    {
        return $this->mapTemplates;
    }

    /**
     * @param MapTemplate $mapTemplate
     *
     * @return UserSystem
     */
    public function addMapTemplate(MapTemplate $mapTemplate): UserSystem
    {
        $this->mapTemplates[] = $mapTemplate;

        return $this;
    }

}