<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Crypt\CryptException;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use InvalidArgumentException;
use MongoDate;
use Throwable;

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

    private const ID                  = '_id';
    public const  USER                = 'user';
    public const  TOKEN               = 'token';
    public const  SYSTEM              = 'system';
    public const  EXPIRES             = 'expires';
    public const  SYNCHRONIZED        = 'synchronized';
    public const  SYNCHRONIZED_TIME   = 'synchronizedTime';
    public const  CREATED             = 'created';
    public const  ENCRYPTED_SETTINGS  = 'encryptedSettings';
    public const  EVENT_CREATE        = 'eventCreate';
    public const  EVENT_UNSUBSCRIBE   = 'eventUnsubscribe';
    public const  EVENT_HARD_BOUNCE   = 'eventHardBounce';
    public const  EVENT_SUBSCRIBE     = 'eventSubscribe';
    public const  PLUGIN_VERSION      = 'pluginVersion';
    public const  SYSTEM_URL          = 'system_url';
    public const  DISTRIBUTION_LISTS  = 'distribution_lists';
    public const  DISTRIBUTION_LIST   = 'distribution_list';
    public const  REMOTE_HOST         = 'remote_host';
    public const  SELECT_LIST         = 'list';
    public const  FORMS               = 'custom_form';
    public const  SYSTEM_LIMITS       = 'system_limits';
    public const  SYSTEM_LIMIT_VALUE  = 'system_limit_value';
    public const  SYSTEM_LIMIT_UPDATE = 'system_limit_update';

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
     * @var DateTime|null
     *
     * @ODM\Field(type="date")
     */
    protected $expires;

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
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    protected $eventCreate = FALSE;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    protected $eventUnsubscribe = FALSE;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    protected $eventHardBounce = FALSE;

    /**
     * @var bool
     *
     * @ODM\Field(type="bool")
     */
    protected $eventSubscribe = FALSE;

    /**
     * @var null|string
     *
     * @ODM\Field(type="string")
     */
    protected $pluginVersion = NULL;

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
     * @param DateTime|null $expires
     *
     * @return SystemInstall
     */
    public function setExpires(?DateTime $expires): SystemInstall
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getExpires(): ?DateTime
    {
        return $this->expires;
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
     * @return bool
     */
    public function isEventCreate(): bool
    {
        return $this->eventCreate;
    }

    /**
     * @param bool $eventCreate
     *
     * @return SystemInstall
     */
    public function setEventCreate(bool $eventCreate): SystemInstall
    {
        $this->eventCreate = $eventCreate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEventUnsubscribe(): bool
    {
        return $this->eventUnsubscribe;
    }

    /**
     * @param bool $eventUnsubscribe
     *
     * @return SystemInstall
     */
    public function setEventUnsubscribe(bool $eventUnsubscribe): SystemInstall
    {
        $this->eventUnsubscribe = $eventUnsubscribe;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEventHardBounce(): bool
    {
        return $this->eventHardBounce;
    }

    /**
     * @param bool $eventHardBounce
     *
     * @return SystemInstall
     */
    public function setEventHardBounce(bool $eventHardBounce): SystemInstall
    {
        $this->eventHardBounce = $eventHardBounce;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEventSubscribe(): bool
    {
        return $this->eventSubscribe;
    }

    /**
     * @param bool $eventSubscribe
     *
     * @return SystemInstall
     */
    public function setEventSubscribe(bool $eventSubscribe): SystemInstall
    {
        $this->eventSubscribe = $eventSubscribe;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPluginVersion(): ?string
    {
        return $this->pluginVersion;
    }

    /**
     * @param string|null $pluginVersion
     *
     * @return SystemInstall
     */
    public function setPluginVersion(?string $pluginVersion): SystemInstall
    {
        $this->pluginVersion = $pluginVersion;

        return $this;
    }

    /**
     * Encrypts settings field before saving to storage
     *
     * @ODM\PreFlush
     * @throws CryptException
     */
    public function encrypt(): void
    {
        $this->encryptedSettings = CryptManager::encrypt($this->settings);
    }

    /**
     * Decrypts settings field when loading from storage
     *
     * @ODM\PostLoad
     * @throws CryptException
     */
    public function decrypt(): void
    {
        $this->settings = CryptManager::decrypt($this->encryptedSettings);
    }

    /**
     * @param string $event
     *
     * @throws CleverConnectorsException
     */
    public static function checkEvent(string $event): void
    {
        if (
        !in_array($event, [self::EVENT_CREATE, self::EVENT_UNSUBSCRIBE, self::EVENT_HARD_BOUNCE, self::EVENT_SUBSCRIBE])
        ) {
            throw new CleverConnectorsException(
                sprintf('Event type ["%s"] is not valid.', $event),
                CleverConnectorsException::INVALID_ENUM_VALUE
            );
        };
    }

    /**
     * @param string $event
     *
     * @return bool
     */
    public function getEventState(string $event): bool
    {
        switch ($event) {
            case self::EVENT_CREATE:
                return $this->isEventCreate();
            case self::EVENT_UNSUBSCRIBE:
                return $this->isEventUnsubscribe();
            case self::EVENT_HARD_BOUNCE:
                return $this->isEventHardBounce();
            case self::EVENT_SUBSCRIBE:
                return $this->isEventSubscribe();
            default:
                throw new InvalidArgumentException(sprintf('Unsupported event type "%s"', $event));
        }
    }

    /**
     * @param string $event
     * @param bool   $value
     *
     * @return SystemInstall
     */
    public function setEventState(string $event, bool $value): SystemInstall
    {
        switch ($event) {
            case self::EVENT_CREATE:
                $this->setEventCreate($value);
                break;
            case self::EVENT_UNSUBSCRIBE:
                $this->setEventUnsubscribe($value);
                break;
            case self::EVENT_HARD_BOUNCE:
                $this->setEventHardBounce($value);
                break;
            case self::EVENT_SUBSCRIBE:
                $this->setEventSubscribe($value);
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unsupported event type "%s"', $event));
        }

        return $this;
    }

    /**
     * @param array $data
     *
     * @return SystemInstall
     * @throws CleverConnectorsException
     */
    public static function from(array $data): SystemInstall
    {
        $systemInstall = new SystemInstall();

        if (array_key_exists(self::ID, $data)) {
            if (is_array($data[self::ID])) {
                $systemInstall->id = $data[self::ID]['$id'];
            } else {
                $systemInstall->id = $data[self::ID] ?? '';
            }
        }

        $systemInstall
            ->setUser($data[self::USER] ?? '')
            ->setToken($data[self::TOKEN] ?? '')
            ->setSystem($data[self::SYSTEM] ?? '')
            ->setSynchronized((bool) ($data[self::SYNCHRONIZED] ?? FALSE))
            ->setEventCreate((bool) ($data[self::EVENT_CREATE] ?? FALSE))
            ->setEventUnsubscribe((bool) ($data[self::EVENT_UNSUBSCRIBE] ?? FALSE))
            ->setEventHardBounce((bool) ($data[self::EVENT_HARD_BOUNCE] ?? FALSE))
            ->setEventSubscribe((bool) ($data[self::EVENT_SUBSCRIBE] ?? FALSE))
            ->setPluginVersion($data[self::PLUGIN_VERSION] ?? NULL)
            ->setSettings([]);

        if (isset($data[self::ENCRYPTED_SETTINGS])) {
            try {
                $systemInstall->setSettings(CryptManager::decrypt($data[self::ENCRYPTED_SETTINGS]));
            } catch (Throwable $t) {
                throw new CleverConnectorsException($t->getMessage(), $t->getCode(), $t->getPrevious());
            }
        }

        $synchronizedTime = self::prepareDate($data, self::SYNCHRONIZED_TIME);
        if ($synchronizedTime) {
            $systemInstall->setSynchronizedTime($synchronizedTime);
        }

        $systemInstall
            ->setExpires(self::prepareDate($data, self::EXPIRES))
            ->setCreated(self::prepareDate($data, self::CREATED) ?? new DateTime('now', new DateTimeZone('UTC')));

        return $systemInstall;
    }

    /**
     * @param array  $data
     * @param string $key
     *
     * @return DateTime|null
     */
    private static function prepareDate(array $data, string $key): ?DateTime
    {
        $date = NULL;
        if (array_key_exists($key, $data) && !empty($data[$key])) {
            if (is_array($data[$key])) {
                $date = new MongoDate($data[$key]['sec'], $data[$key]['usec']);
                $date = $date->toDateTime();
            } else {
                $date = new DateTime($data[$key]);
            }
        }

        return $date;
    }

}