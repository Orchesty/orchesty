<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemTopologyRunner;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\InnerRequestUtils;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
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
     * @var int
     */
    private $limitRefreshInterval;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var SystemTopologyRunner
     */
    private $systemTopologyRunner;

    /**
     * SystemLimitManager constructor.
     *
     * @param SystemLoader         $systemLoader
     * @param SystemTopologyRunner $systemTopologyRunner
     * @param DocumentManager      $dm
     * @param int                  $limitRefreshInterval
     */
    public function __construct(
        SystemLoader $systemLoader,
        SystemTopologyRunner $systemTopologyRunner,
        DocumentManager $dm,
        int $limitRefreshInterval
    )
    {
        $this->systemLoader            = $systemLoader;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->limitRefreshInterval    = $limitRefreshInterval;
        $this->systemTopologyRunner    = $systemTopologyRunner;
    }

    /**
     * @param HeaderBag            $headers
     * @param SystemInterface|null $system
     * @param SystemInstall|null   $systemInstall
     */
    public function addSystemLimitToRequestHeaders(
        HeaderBag $headers,
        ?SystemInterface $system = NULL,
        ?SystemInstall $systemInstall = NULL
    ): void
    {
        if (empty($system)) {
            $system = $this->systemLoader->getSystem($headers->get(CMHeaders::createKey(CMHeaders::SYSTEM_KEY)));
        }
        if (empty($systemInstall)) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($headers->all());
        }
        $dto = $system->getLimit($systemInstall);
        $this->checkLimitRefresh($dto, $system, $systemInstall);

        if ($dto) {
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER), $dto->getLimitKey());
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_LAST_UPDATE),
                $dto->getLastUpdate() ? $dto->getLastUpdate()->getTimestamp() : NULL);
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER), $dto->getLimitTime());
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER), $dto->getLimitValue());
        }
    }

    /**
     * @param SuccessMessage $successMessage
     */
    public function addSystemLimitToSuccessMessage(SuccessMessage $successMessage): void
    {
        $system        = $this->systemLoader->getSystem($successMessage->getHeader(CMHeaders::createKey(CMHeaders::SYSTEM_KEY)));
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($successMessage->getHeaders());

        $dto = $system->getLimit($systemInstall);
        $this->checkLimitRefresh($dto, $system, $systemInstall);

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

    /**
     * @param SystemLimitDto|null $dto
     * @param SystemInterface     $system
     * @param SystemInstall       $systemInstall
     */
    private function checkLimitRefresh(
        ?SystemLimitDto $dto,
        SystemInterface $system,
        SystemInstall $systemInstall
    ): void
    {
        $timestamp = (new DateTime())->getTimestamp() - $this->limitRefreshInterval;
        if (empty($dto) || empty($dto->getLastUpdate()) || $dto->getLastUpdate()->getTimestamp() < $timestamp) {
            $this->systemTopologyRunner->runTopologies(
                TopologyNameUtils::GET_LIMIT,
                $systemInstall,
                $system,
                InnerRequestUtils::getRequest($systemInstall, [])
            );
        }
    }

}