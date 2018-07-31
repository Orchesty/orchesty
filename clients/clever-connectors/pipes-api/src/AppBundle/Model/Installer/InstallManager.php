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
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;
use Predis\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
     * @var CategoryParser
     */
    private $categoryParser;

    /**
     * InstallManager constructor.
     *
     * @param DocumentManager $dm
     * @param Client          $client
     * @param TopologyManager $topologyManager
     * @param RequestHandler  $requestHandler
     * @param CategoryParser  $categoryParser
     * @param array           $dirs
     */
    public function __construct(
        DocumentManager $dm,
        Client $client,
        TopologyManager $topologyManager,
        RequestHandler $requestHandler,
        CategoryParser $categoryParser,
        array $dirs
    )
    {
        $this->dm = $dm;
        /** @var TopologyRepository $repo */
        $repo                  = $dm->getRepository(Topology::class);
        $this->client          = $client;
        $this->topologyManager = $topologyManager;
        $this->requestHandler  = $requestHandler;
        $this->categoryParser  = $categoryParser;
        $this->comparator      = new TopologiesComparator($repo, $dirs);
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
    public function setLogger(LoggerInterface $logger): void
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
     * @throws CleverConnectorsException
     */
    public function prepareInstall(bool $makeCreate, bool $makeUpdate, bool $makeDelete, bool $force = FALSE): array
    {
        $result = $this->comparator->compare();
        $this->client->set(self::AUTO_INSTALL_KEY, serialize($result));

        if ($force) {
            return $this->makeInstall($makeCreate, $makeUpdate, $makeDelete);
        }

        return $this->generateOutput($result, $makeCreate, $makeUpdate, $makeDelete);
    }

    /**
     * @param bool $makeCreate
     * @param bool $makeUpdate
     * @param bool $makeDelete
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function makeInstall(bool $makeCreate, bool $makeUpdate, bool $makeDelete): array
    {
        $record = $this->client->get(self::AUTO_INSTALL_KEY);

        if (!$record) {
            throw new CleverConnectorsException('Redis record not found!. Please run prepareInstall first.');
        }

        /** @var CompareResultDto $result */
        $result = unserialize($record);
        $errors = [];

        if ($makeCreate) {
            $errors[self::CREATE] = $this->makeCreate($result);
        }

        if ($makeUpdate) {
            $errors[self::UPDATE] = $this->makeUpdate($result);
        }

        if ($makeDelete) {
            $errors[self::DELETE] = $this->makeDelete($result);
        }

        $this->client->del([self::AUTO_INSTALL_KEY]);

        return $errors;
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
     *
     * @return array
     */
    private function makeCreate(CompareResultDto $dto): array
    {
        $output = [];

        foreach ($dto->getCreate() as $file) {
            try {
                $message  = '';
                $topology = $this->topologyManager->createTopology(
                    ['name' => TplgLoader::getName($file->getName()), 'enabled' => TRUE]
                );
                $this->makeRunnable($topology, $file->getContents());
                $this->categoryParser->classifyTopology($topology, $file);
            } catch (Throwable $e) {
                $this->logException($e, self::CREATE);
                $message = $e->getMessage();
            }
            $output[TplgLoader::getName($file->getName())] = $message;
        }

        return $output;
    }

    /**
     * @param CompareResultDto $dto
     *
     * @return array
     */
    private function makeUpdate(CompareResultDto $dto): array
    {
        $errors = [];
        /** @var UpdateObject $obj */
        foreach ($dto->getUpdate() as $obj) {
            try {
                $message     = '';
                $oldTopology = $obj->getTopology();
                $this->dm->persist($oldTopology);
                $topology = $this->makeRunnable($oldTopology, $obj->getFile()->getContents());
                $this->categoryParser->classifyTopology($topology, $obj->getFile());

                if ($topology->getId() != $oldTopology->getId()) {
                    $this->makeDeletable($oldTopology);
                }
            } catch (Throwable $e) {
                $this->logException($e, self::UPDATE);
                $message = $e->getMessage();
            }
            $errors[$obj->getTopology()->getName()] = $message;
        }

        return $errors;
    }

    /**
     * @param CompareResultDto $dto
     *
     * @return array
     */
    private function makeDelete(CompareResultDto $dto): array
    {
        $errors = [];
        /** @var Topology $topology */
        foreach ($dto->getDelete() as $topology) {
            try {
                $message = '';
                $this->dm->persist($topology);
                $this->makeDeletable($topology);
            } catch (Throwable $e) {
                $this->logException($e, self::DELETE);
                $message = $e->getMessage();
            }
            $errors[$topology->getName()] = $message;
        }

        return $errors;
    }

    /**
     * @param Topology $topology
     * @param string   $content
     *
     * @return Topology
     */
    private function makeRunnable(Topology $topology, string $content): Topology
    {
        $topology = $this->topologyManager->saveTopologySchema($topology, $content, $this->xml->decode($content));
        $this->topologyManager->publishTopology($topology);
        $this->topologyManager->updateTopology($topology, ['enabled' => TRUE]);
        $this->requestHandler->generateTopology($topology->getId());
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
        $this->logger->error(sprintf('Error occurred during %s action.', $action), ['exception' => $e]);
    }

}