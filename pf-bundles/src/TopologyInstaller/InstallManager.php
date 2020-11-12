<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\TopologyInstaller\Cache\TopologyInstallerCacheInterface;
use Hanaboso\PipesFramework\TopologyInstaller\Dto\CompareResultDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\RestBundle\Exception\XmlDecoderException;
use Hanaboso\RestBundle\Model\Decoder\XmlDecoder;
use Hanaboso\Utils\Exception\EnumException;
use JsonException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class InstallManager
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller
 */
final class InstallManager implements LoggerAwareInterface
{

    public const AUTO_INSTALL_KEY = 'auto-install-key';

    private const CREATE = 'create';
    private const UPDATE = 'update';
    private const DELETE = 'delete';

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var TopologiesComparator
     */
    private TopologiesComparator $comparator;

    /**
     * @var TopologyManager
     */
    private TopologyManager $topologyManager;

    /**
     * @var TopologyGeneratorBridge
     */
    private TopologyGeneratorBridge $requestHandler;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CategoryParser
     */
    private CategoryParser $categoryParser;

    /**
     * @var XmlDecoder
     */
    private XmlDecoder $decoder;

    /**
     * @var TopologyInstallerCacheInterface
     */
    private TopologyInstallerCacheInterface $installerCache;

    /**
     * InstallManager constructor.
     *
     * @param DocumentManager                 $dm
     * @param TopologyManager                 $topologyManager
     * @param TopologyGeneratorBridge         $requestHandler
     * @param CategoryParser                  $categoryParser
     * @param XmlDecoder                      $decoder
     * @param TopologyInstallerCacheInterface $installerCache
     * @param mixed[]                         $dirs
     */
    public function __construct(
        DocumentManager $dm,
        TopologyManager $topologyManager,
        TopologyGeneratorBridge $requestHandler,
        CategoryParser $categoryParser,
        XmlDecoder $decoder,
        TopologyInstallerCacheInterface $installerCache,
        array $dirs
    )
    {
        $this->dm              = $dm;
        $this->topologyManager = $topologyManager;
        $this->requestHandler  = $requestHandler;
        $this->categoryParser  = $categoryParser;
        $this->decoder         = $decoder;
        $this->comparator      = new TopologiesComparator($dm->getRepository(Topology::class), $decoder, $dirs);
        $this->logger          = new NullLogger();
        $this->installerCache  = $installerCache;
    }

    /**
     * @param bool $makeCreate
     * @param bool $makeUpdate
     * @param bool $makeDelete
     * @param bool $force
     *
     * @return mixed[]
     * @throws ConnectorException
     * @throws MongoDBException
     * @throws TopologyException
     * @throws XmlDecoderException
     */
    public function prepareInstall(bool $makeCreate, bool $makeUpdate, bool $makeDelete, bool $force = FALSE): array
    {
        $result = $this->generateResult();
        $this->installerCache->set(self::AUTO_INSTALL_KEY, $result);

        if ($force) {
            return $this->makeInstall($makeCreate, $makeUpdate, $makeDelete);
        }

        return $this->generateOutput($result, $makeCreate, $makeUpdate, $makeDelete);
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
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws TopologyException
     * @throws XmlDecoderException
     */
    public function makeInstall(bool $makeCreate, bool $makeUpdate, bool $makeDelete): array
    {
        $result = $this->installerCache->get(self::AUTO_INSTALL_KEY);
        $errors = [];

        if ($result === NULL) {
            $result = $this->generateResult();
        }

        if ($makeCreate) {
            $errors[self::CREATE] = $this->makeCreate($result);
        }

        if ($makeUpdate) {
            $errors[self::UPDATE] = $this->makeUpdate($result);
        }

        if ($makeDelete) {
            $errors[self::DELETE] = $this->makeDelete($result);
        }

        $this->installerCache->delete(self::AUTO_INSTALL_KEY);

        return $errors;
    }

    /**
     * @return CompareResultDto
     * @throws MongoDBException
     * @throws TopologyException
     * @throws XmlDecoderException
     */
    private function generateResult(): CompareResultDto
    {
        return $this->comparator->compare();
    }

    /**
     * @param CompareResultDto $dto
     * @param bool             $makeCreate
     * @param bool             $makeUpdate
     * @param bool             $makeDelete
     *
     * @return mixed[]
     */
    private function generateOutput(CompareResultDto $dto, bool $makeCreate, bool $makeUpdate, bool $makeDelete): array
    {
        return $dto->toArray($makeCreate, $makeUpdate, $makeDelete);
    }

    /**
     * @param CompareResultDto $dto
     *
     * @return mixed[]
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
                $this->makeRunnable($topology, $file->getFileContents());
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
     * @return mixed[]
     */
    private function makeUpdate(CompareResultDto $dto): array
    {
        $errors = [];
        foreach ($dto->getUpdate() as $obj) {
            try {
                $message     = '';
                $oldTopology = $obj->getTopology();
                $this->dm->persist($oldTopology);
                $topology = $this->makeRunnable($oldTopology, $obj->getFile()->getFileContents());
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
     * @return mixed[]
     */
    private function makeDelete(CompareResultDto $dto): array
    {
        $errors = [];
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
     * @throws CronException
     * @throws CurlException
     * @throws EnumException
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws NodeException
     * @throws TopologyConfigException
     * @throws TopologyException
     * @throws XmlDecoderException
     * @throws JsonException
     */
    private function makeRunnable(Topology $topology, string $content): Topology
    {
        $topology = $this->topologyManager->saveTopologySchema($topology, $content, $this->decoder->decode($content));
        $this->topologyManager->publishTopology($topology);
        $this->topologyManager->updateTopology($topology, ['enabled' => TRUE]);
        $this->requestHandler->generateTopology($topology->getId());
        $this->requestHandler->runTopology($topology->getId());

        return $topology;
    }

    /**
     * @param Topology $topology
     *
     * @throws CronException
     * @throws CurlException
     * @throws MongoDBException
     * @throws TopologyException
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
