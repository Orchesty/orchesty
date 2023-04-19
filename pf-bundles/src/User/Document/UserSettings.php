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
     * @var mixed[]
     *
     * @ODM\Field(type="hash")
     */
    private array $settings;

    /**
     * @ODM\Field(type="string")
     */
    private string $userId;

    /**
     * @return mixed[]
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param mixed[] $settings
     *
     * @return UserSettings
     */
    public function setSettings(array $settings): self
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
