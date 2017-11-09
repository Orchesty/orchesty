<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.11.17
 * Time: 11:13
 */

namespace CleverConnectors\AppBundle\Model\Installer;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Installer\Dto\CompareResultDto;
use CleverConnectors\AppBundle\Model\Installer\Dto\UpdateObject;
use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\RestBundle\Decoder\XmlDecoder;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use Predis\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

/**
 * Class InstallManager
 *
 * @package CleverConnectors\AppBundle\Model\Installer
 */
class InstallManager implements LoggerAwareInterface
{

    public const AUTO_INSTALL_KEY = 'auto-install-key';

    private const CREATE = 'create';
    private const UPDATE = 'update';
    private const DELETE = 'delete';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var TopologiesComparator
     */
    private $comparator;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var TopologyManager
     */
    private $topologyManager;

    /**
     * @var XmlDecoder
     */
    private $xml;

    /**
     * @var RequestHandler
     */
    private $requestHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * InstallManager constructor.
     *
     * @param DocumentManager $dm
     * @param Client          $client
     * @param TopologyManager $topologyManager
     * @param RequestHandler  $requestHandler
     * @param array           $dirs
     */
    public function __construct(
        DocumentManager $dm,
        Client $client,
        TopologyManager $topologyManager,
        RequestHandler $requestHandler,
        array $dirs
    )
    {
        $this->dm              = $dm;
        $this->client          = $client;
        $this->topologyManager = $topologyManager;
        $this->requestHandler  = $requestHandler;
        $this->comparator      = new TopologiesComparator($dm->getRepository(Topology::class), $dirs);
        $this->xml             = new XmlDecoder();
        $this->logger          = new NullLogger();
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param bool $makeCreate
     * @param bool $makeUpdate
     * @param bool $makeDelete
     * @param bool $force
     *
     * @return array
     */
    public function prepareInstall(bool $makeCreate, bool $makeUpdate, bool $makeDelete, bool $force = TRUE): array
    {
        $result = $this->comparator->compare();
        $this->client->set(self::AUTO_INSTALL_KEY, json_encode($result));

        if ($force) {
            $this->makeInstall($makeCreate, $makeUpdate, $makeDelete);
        }

        return $this->generateOutput($result, $makeCreate, $makeUpdate, $makeDelete);
    }

    /**
     * @param bool $makeCreate
     * @param bool $makeUpdate
     * @param bool $makeDelete
     *
     * @throws CleverConnectorsException
     */
    public function makeInstall(bool $makeCreate, bool $makeUpdate, bool $makeDelete): void
    {
        $record = $this->client->get(self::AUTO_INSTALL_KEY);

        if (!$record) {
            throw new CleverConnectorsException('Redis record not found!. Please run prepareInstall first.');
        }

        /** @var CompareResultDto $result */
        $result = json_decode($record);

        if ($makeCreate) {
            $this->makeCreate($result);
        }

        if ($makeUpdate) {
            $this->makeUpdate($result);
        }

        if ($makeDelete) {
            $this->makeDelete($result);
        }
    }

    /**
     * -------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param CompareResultDto $dto
     * @param bool             $makeCreate
     * @param bool             $makeUpdate
     * @param bool             $makeDelete
     *
     * @return array
     */
    private function generateOutput(CompareResultDto $dto, bool $makeCreate, bool $makeUpdate, bool $makeDelete): array
    {
        return $dto->toArray($makeCreate, $makeUpdate, $makeDelete);
    }

    /**
     * @param CompareResultDto $dto
     */
    private function makeCreate(CompareResultDto $dto): void
    {
        /** @var SplFileInfo $file */
        foreach ($dto->getCreate() as $file) {
            try {
                $topology = $this->topologyManager->createTopology(
                    ['name' => TplgLoader::getName($file), 'enabled' => TRUE]
                );
                $this->makeRunnable($topology, $file->getContents());
            } catch (Throwable $e) {
                $this->logException($e, self::CREATE);
            }
        }
    }

    /**
     * @param CompareResultDto $dto
     */
    private function makeUpdate(CompareResultDto $dto): void
    {
        /** @var UpdateObject $obj */
        foreach ($dto->getUpdate() as $obj) {
            try {
                $oldTopology = $obj->getTopology();
                $this->dm->persist($oldTopology);
                $topology = $this->makeRunnable($oldTopology, $obj->getFile()->getContents());

                if ($topology->getId() != $oldTopology->getId()) {
                    $this->makeDeletable($oldTopology);
                }
            } catch (Throwable $e) {
                $this->logException($e, self::UPDATE);
            }
        }
    }

    /**
     * @param CompareResultDto $dto
     */
    private function makeDelete(CompareResultDto $dto): void
    {
        /** @var Topology $topology */
        foreach ($dto->getDelete() as $topology) {
            try {
                $this->dm->persist($topology);
                $this->makeDeletable($topology);
            } catch (Throwable $e) {
                $this->logException($e, self::DELETE);
            }
        }
    }

    /**
     * @param Topology $topology
     * @param string   $content
     *
     * @return Topology
     */
    private function makeRunnable(Topology $topology, string $content): Topology
    {
        $this->topologyManager->publishTopology($topology);
        $topology = $this->topologyManager->saveTopologySchema($topology, $content, $this->xml->decode($content));
        $this->requestHandler->runTopology($topology->getId());

        return $topology;
    }

    /**
     * @param Topology $topology
     */
    private function makeDeletable(Topology $topology): void
    {
        $this->topologyManager->updateTopology($topology, ['enabled' => FALSE]);
        $this->requestHandler->deleteTopology($topology->getId());
        $this->topologyManager->deleteTopology($topology);
    }

    /**
     * @param Throwable $e
     * @param string    $action
     */
    private function logException(Throwable $e, string $action): void
    {
        $this->logger->error(sprintf('Error occurred after %s action.', $action), ['exception' => $e]);
    }

}