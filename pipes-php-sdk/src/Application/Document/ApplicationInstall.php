<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Document;

use DateTime;
use Exception;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\DocumentAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class ApplicationInstall
 *
 * @package Hanaboso\PipesPhpSdk\Application\Document
 */
class ApplicationInstall extends DocumentAbstract
{

    public const USER = 'user';
    public const NAME = 'name';

    /**
     * @var DateTime|null
     */
    protected ?DateTime $created;

    /**
     * @var DateTime|null
     */
    protected ?DateTime $updated;

    /**
     * @var bool
     */
    protected bool $deleted = FALSE;

    /**
     * @var string|null
     */
    private ?string $user = NULL;

    /**
     * @var string|null
     */
    private ?string $key = NULL;

    /**
     * @var bool
     */
    private bool $enabled = FALSE;

    /**
     * @var DateTime|null
     */
    private ?DateTime $expires = NULL;

    /**
     * @var mixed[]
     */
    private array $settings = [];

    /**
     * @var string
     */
    private string $encryptedSettings = '';

    /**
     * @var mixed[]
     */
    private array $nonEncryptedSettings = [];

    /**
     * ApplicationInstall constructor.
     *
     * @param mixed[]|null $data
     *
     * @throws DateTimeException
     */
    public function __construct(?array $data = [])
    {
        if (empty($data['created'])) {
            $this->created = DateTimeUtils::getUtcDateTime();
        }
        $this->updated = DateTimeUtils::getUtcDateTime();

        parent::__construct($data);
    }

    /**
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime|null $created
     *
     * @return self
     */
    public function setCreated(?DateTime $created): self
    {
        $this->created = $created ?? new DateTime();

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    /**
     * @param DateTime|null $updated
     *
     * @return self
     */
    public function setUpdated(?DateTime $updated): self
    {
        $this->updated = $updated ?? new DateTime();

        return $this;
    }

    /**
     * @throws DateTimeException
     */
    public function preUpdate(): void
    {
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool|null $deleted
     *
     * @return self
     */
    public function setDeleted(?bool $deleted): self
    {
        $this->deleted = $deleted ?? FALSE;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param mixed[]|null $settings
     *
     * @return self
     */
    public function setSettings(?array $settings): self
    {
        $this->settings = $settings ?? [];

        return $this;
    }

    /**
     * @param mixed[] $settings
     *
     * @return self
     */
    public function addSettings(array $settings): self
    {
        $this->settings = array_merge($this->settings, $settings);

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
     * @param string|null $encryptedSettings
     *
     * @return self
     */
    public function setEncryptedSettings(?string $encryptedSettings): self
    {
        $this->encryptedSettings = $encryptedSettings ?? '';

        return $this;
    }

    /**
     * @param string|null $user
     *
     * @return self
     */
    public function setUser(?string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @return DateTime|null
     */
    public function getExpires(): ?DateTime
    {
        return $this->expires;
    }

    /**
     * @param DateTime|null $expires
     *
     * @return self
     */
    public function setExpires(?DateTime $expires): self
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     *
     * @return self
     */
    public function setKey(?string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getNonEncryptedSettings(): ?array
    {
        return $this->nonEncryptedSettings;
    }

    /**
     * @param mixed[] $nonEncryptedSettings
     *
     * @return self
     */
    public function setNonEncryptedSettings(array $nonEncryptedSettings): self
    {
        $this->nonEncryptedSettings = $nonEncryptedSettings;

        return $this;
    }

    /**
     * @param mixed[] $nonEncryptedSettings
     *
     * @return self
     */
    public function addNonEncryptedSettings(array $nonEncryptedSettings): self
    {
        $this->nonEncryptedSettings = array_merge($this->nonEncryptedSettings, $nonEncryptedSettings);

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool|null $enabled
     *
     * @return self
     */
    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled ?? FALSE;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $expires = $this->getExpires();

        return [
            'created'              => $this->getCreated()?->format(DateTimeUtils::DATE_TIME),
            'enabled'              => $this->isEnabled(),
            'encryptedSettings'    => $this->getEncryptedSettings(),
            'expires'              => $expires?->format(DateTimeUtils::DATE_TIME),
            'id'                   => $this->getId(),
            'nonEncryptedSettings' => $this->getNonEncryptedSettings(),
            'settings'             => $this->getSettings(),
            'updated'              => $this->getUpdated()?->format(DateTimeUtils::DATE_TIME),
            self::NAME             => $this->getKey(),
            self::USER             => $this->getUser(),
        ];
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'ApplicationInstall';
    }

    /**
     * @param mixed[] $data
     *
     * @return self
     * @throws Exception
     */
    protected function fromArray(array $data): self
    {
        if (array_key_exists('id', $data))
            $this->setId($data['id']);
        if (array_key_exists(self::USER, $data))
            $this->setUser($data[self::USER]);
        if (array_key_exists(self::NAME, $data))
            $this->setKey($data[self::NAME]);
        if (array_key_exists('nonEncryptedSettings', $data))
            $this->setNonEncryptedSettings($data['nonEncryptedSettings']);
        if (array_key_exists('encryptedSettings', $data))
            $this->setEncryptedSettings($data['encryptedSettings']);
        if (array_key_exists('settings', $data))
            $this->setSettings($data['settings']);
        if (array_key_exists('created', $data))
            $this->setCreated($data['created'] ? new DateTime($data['created']) : NULL);
        if (array_key_exists('updated', $data))
            $this->setUpdated($data['updated'] ? new DateTime($data['updated']) : NULL);
        if (array_key_exists('expires', $data))
            $this->setExpires($data['expires'] ? new DateTime($data['expires']) : NULL);
        if (array_key_exists('enabled', $data))
            $this->setEnabled($data['enabled']);

        return $this;
    }

}
