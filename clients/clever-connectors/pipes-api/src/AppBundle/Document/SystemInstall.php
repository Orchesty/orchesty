<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;

/**
 * Class SystemInstall
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\SystemInstallRepository")
 *
 * @ODM\HasLifecycleCallbacks
 */
class SystemInstall
{

    private const USER         = 'user';
    private const TOKEN        = 'token';
    private const SYSTEM       = 'system';
    private const SYNCHRONIZED = 'synchronized';
    private const SETTINGS     = 'settings';

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    protected $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $token;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $system;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean")
     */
    protected $synchronized = FALSE;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    protected $synchronizedTime;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    protected $created;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $encryptedSettings;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * SystemInstall constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime();
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return SystemInstall
     */
    public function setUser(string $user): SystemInstall
    {
        $this->user = $user;

        return $this;
    }

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
     * @return SystemInstall
     */
    public function setToken(string $token): SystemInstall
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getSystem(): string
    {
        return $this->system;
    }

    /**
     * @param string $system
     *
     * @return SystemInstall
     */
    public function setSystem(string $system): SystemInstall
    {
        $this->system = $system;

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
     * @return SystemInstall
     */
    public function setSynchronized(bool $synchronized): SystemInstall
    {
        $this->synchronized = $synchronized;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     *
     * @return SystemInstall
     */
    public function setCreated(DateTime $created): SystemInstall
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     *
     * @return SystemInstall
     */
    public function setSettings(array $settings): SystemInstall
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getSynchronizedTime(): ?DateTime
    {
        return $this->synchronizedTime;
    }

    /**
     * @param DateTime $synchronizedTime
     *
     * @return $this
     */
    public function setSynchronizedTime(DateTime $synchronizedTime)
    {
        $this->synchronizedTime = $synchronizedTime;

        return $this;
    }

    /**
     * Encrypts settings field before saving to storage
     *
     * @ODM\PreFlush
     */
    public function encrypt(): void
    {
        $this->encryptedSettings = CryptManager::encrypt($this->settings);
    }

    /**
     * Decrypts settings field when loading from storage
     *
     * @ODM\PostLoad
     */
    public function decrypt(): void
    {
        $this->settings = CryptManager::decrypt($this->encryptedSettings);
    }

    /**
     * @param array $data
     *
     * @return SystemInstall
     */
    public static function from(array $data): SystemInstall
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser($data[self::USER] ?? '')
            ->setToken($data[self::TOKEN] ?? '')
            ->setSystem($data[self::SYSTEM] ?? '')
            ->setSynchronized((bool) ($data[self::SYNCHRONIZED] ?? FALSE))
            ->setSettings($data[self::SETTINGS] ?? []);

        return $systemInstall;
    }

}
