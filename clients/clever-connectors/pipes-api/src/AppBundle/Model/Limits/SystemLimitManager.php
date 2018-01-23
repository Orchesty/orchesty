<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Class SystemLimitManager
 *
 * @package CleverConnectors\AppBundle\Model\Limits
 */
class SystemLimitManager
{

    /**
     * @var SystemLoader
     */
    private $systemLoader;

    /**
     * SystemLimitManager constructor.
     *
     * @param SystemLoader $systemLoader
     */
    public function __construct(SystemLoader $systemLoader)
    {
        $this->systemLoader = $systemLoader;
    }

    /**
     * @param string $systemKey
     *
     * @return SystemLimitInterface|null
     */
    public function getSystemLimitBySystemKey(string $systemKey): ?SystemLimitInterface
    {
        $system = $this->systemLoader->getSystem($systemKey);
        if ($system instanceof SystemLimitInterface) {
            return $system;
        } else {
            return NULL;
        }

    }

    /**
     * @param SystemLimitInterface $systemLimit
     * @param SystemInstall        $systemInstall
     * @param HeaderBag            $headers
     */
    public function addSystemLimitToRequestHeaders(SystemLimitInterface $systemLimit, SystemInstall $systemInstall,
                                                   HeaderBag $headers): void
    {
        $dto = $systemLimit->getLimit($systemInstall);

        if ($dto) {
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER), $dto->getLimitKey());
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_LAST_UPDATE),
                $dto->getLastUpdate() ? $dto->getLastUpdate()->getTimestamp() : NULL);
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER), $dto->getLimitTime());
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER), $dto->getLimitValue());
        }
    }

    /**
     * @param SystemLimitInterface $systemLimit
     * @param SystemInstall        $systemInstall
     * @param SuccessMessage       $successMessage
     */
    public function addSystemLimitToSuccessMessage(SystemLimitInterface $systemLimit, SystemInstall $systemInstall,
                                                   SuccessMessage $successMessage): void
    {
        $dto = $systemLimit->getLimit($systemInstall);

        if ($dto) {
            $successMessage->addHeader(CMHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER), $dto->getLimitKey());
            $successMessage->addHeader(CMHeaders::createKey(SystemLimitDto::LIMIT_LAST_UPDATE),
                $dto->getLastUpdate() ? strval($dto->getLastUpdate()->getTimestamp()) : '');
            $successMessage->addHeader(CMHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER),
                strval($dto->getLimitTime()));
            $successMessage->addHeader(CMHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER),
                strval($dto->getLimitValue()));
        }
    }

}