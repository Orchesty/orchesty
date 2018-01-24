<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
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
     * @var StartingPoint
     */
    private $startingPoint;

    /**
     * @var TopologyRepository|ObjectRepository
     */
    private $topologyRepository;

    /**
     * @var NodeRepository|ObjectRepository
     */
    private $nodeRepository;

    /**
     * SystemLimitManager constructor.
     *
     * @param StartingPoint   $startingPoint
     * @param DocumentManager $dm
     */
    public function __construct(StartingPoint $startingPoint, DocumentManager $dm)
    {
        $this->startingPoint      = $startingPoint;
        $this->topologyRepository = $dm->getRepository(Topology::class);
        $this->nodeRepository     = $dm->getRepository(Node::class);
    }

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     * @param HeaderBag       $headers
     */
    public function addSystemLimitToRequestHeaders(SystemInterface $system, SystemInstall $systemInstall,
                                                   HeaderBag $headers): void
    {
        $dto = $system->getLimit($systemInstall);
        $this->checkLimitRefresh($dto, $system);

        if ($dto) {
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER), $dto->getLimitKey());
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_LAST_UPDATE),
                $dto->getLastUpdate() ? $dto->getLastUpdate()->getTimestamp() : NULL);
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER), $dto->getLimitTime());
            $headers->set(CMHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER), $dto->getLimitValue());
        }
    }

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     * @param SuccessMessage  $successMessage
     */
    public function addSystemLimitToSuccessMessage(SystemInterface $system, SystemInstall $systemInstall,
                                                   SuccessMessage $successMessage): void
    {
        $dto = $system->getLimit($systemInstall);
        $this->checkLimitRefresh($dto, $system);

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
     */
    private function checkLimitRefresh(?SystemLimitDto $dto, SystemInterface $system): void
    {
        $timestamp = (new DateTime())->getTimestamp() - 86400;
        if (empty($dto) || empty($dto->getLastUpdate()) || $dto->getLastUpdate()->getTimestamp() < $timestamp) {
            $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::GET_LIMIT, $system->getKey());
            $topologies   = $this->topologyRepository->getRunnableTopologies($topologyName);
            foreach ($topologies as $topology) {
                $node = $this->nodeRepository->getStartingNode($topology);
                $this->startingPoint->run($topology, $node);
            }
        }
    }

}