<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 27.11.17
 * Time: 8:12
 */

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Mapper\UniversalMapper;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Repository\MapTemplateRepository;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class UniversalMapperNode
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
class UniversalMapperNode implements CustomNodeInterface, LoggerAwareInterface
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemRepository;

    /**
     * @var MapTemplateRepository|ObjectRepository
     */
    private $mapRepository;

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $suffix = NULL;

    /**
     * UniversalMapperNode constructor.
     *
     * @param DocumentManager $dm
     * @param SystemLoader    $loader
     */
    public function __construct(DocumentManager $dm, SystemLoader $loader)
    {
        $this->systemRepository = $dm->getRepository(SystemInstall::class);
        $this->mapRepository    = $dm->getRepository(MapTemplate::class);
        $this->loader           = $loader;
        $this->logger           = new NullLogger();
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
     * @param string $suffix
     */
    public function setSuffix(string $suffix): void
    {
        $this->suffix = $suffix;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $template = $this->getMapTemplate($dto);

        if (!$template) {
            return $dto;
        }

        try {
            $mapper = new UniversalMapper();
            $dto    = $mapper
                ->setAllowedEmptyValues(TRUE)
                ->process($template, $dto);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return $dto;
    }

    /**
     * ------------------------------------------ HELPERS ---------------------------------------------
     */

    /**
     * @param ProcessDto $dto
     *
     * @return MapTemplate|null
     */
    private function getMapTemplate(ProcessDto $dto): ?MapTemplate
    {
        $systemInstall = $this->systemRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $actionName    = CMHeaders::get(CMHeaders::TOPOLOGY_NAME, $dto->getHeaders());
        $actions       = $this->loader->getSystem($systemInstall->getSystem())->getAllowedActions();

        if ($this->suffix) {
            $actionName = sprintf('%s-%s', $actionName, $this->suffix);
        }

        if (!array_key_exists($actionName, $actions)) {
            $this->logger->alert(
                sprintf('Not allowed action "%s" found for system "%s"!', $actionName, $systemInstall->getSystem())
            );

            return NULL;
        }

        return $this->mapRepository->findUnique($systemInstall, $actions[$actionName]);
    }

}