<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use DateTime;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;

/**
 * Class SystemLimitDto
 *
 * @package CleverConnectors\AppBundle\Model\Limits
 */
class SystemLimitDto
{

    public const LIMIT_FOR_USER   = 0;
    public const LIMIT_FOR_SYSTEM = 1;

    public const LIMIT_KEY_HEADER   = 'limit-key';
    public const LIMIT_VALUE_HEADER = 'limit-time';
    public const LIMIT_TIME_HEADER  = 'limit-value';
    public const LIMIT_LAST_UPDATE  = 'limit-last-update';

    /**
     * @var string
     */
    private $limitKey;

    /**
     * @var int
     */
    private $limitTime;

    /**
     * @var int
     */
    private $limitValue;

    /**
     * @var DateTime|null
     */
    private $lastUpdate;

    /**
     * SystemLimitDto constructor.
     *
     * @param SystemInstall $systemInstall
     * @param int           $limitType
     * @param int           $limitTime
     * @param int           $limitValue
     * @param DateTime|null $lastUpdate
     */
    function __construct(
        SystemInstall $systemInstall,
        int $limitType,
        int $limitTime,
        int $limitValue,
        ?DateTime $lastUpdate = NULL
    )
    {
        $this->limitTime  = $limitTime;
        $this->limitValue = $limitValue;
        $this->lastUpdate = $lastUpdate;
        $this->limitKey   = $this->createLimitKey($systemInstall, $limitType);
    }

    /**
     * @return string
     */
    public function getLimitKey(): string
    {
        return $this->limitKey;
    }

    /**
     * @return int
     */
    public function getLimitTime(): int
    {
        return $this->limitTime;
    }

    /**
     * @return int
     */
    public function getLimitValue(): int
    {
        return $this->limitValue;
    }

    /**
     * @return DateTime|null
     */
    public function getLastUpdate(): ?DateTime
    {
        return $this->lastUpdate;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            PipesHeaders::createKey(self::LIMIT_KEY_HEADER)   => $this->limitKey,
            PipesHeaders::createKey(self::LIMIT_TIME_HEADER)  => $this->limitTime,
            PipesHeaders::createKey(self::LIMIT_VALUE_HEADER) => $this->limitValue,
            self::LIMIT_LAST_UPDATE                           => $this->lastUpdate,
        ];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param int           $limitType
     *
     * @return string
     */
    private function createLimitKey(SystemInstall $systemInstall, int $limitType): string
    {
        if ($limitType === self::LIMIT_FOR_USER) {
            $key = sprintf('%s-%s', $systemInstall->getUser(), $systemInstall->getSystem());
        } else {
            $key = $systemInstall->getSystem();
        }

        return $key;
    }

}