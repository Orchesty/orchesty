<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class UserSettings
 *
 * @package Hanaboso\PipesFramework\User\Document
 *
 * @ODM\Document()
 */
class UserSettings
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $settings;

    /**
     * @ODM\Field(type="string")
     */
    private string $userId;

    /**
     * @return string
     */
    public function getSettings(): string
    {
        return $this->settings;
    }

    /**
     * @param string $settings
     *
     * @return UserSettings
     */
    public function setSettings(string $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     *
     * @return UserSettings
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

}
