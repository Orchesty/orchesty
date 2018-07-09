<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\NutshellSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class NutshellUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
class NutshellUpdateContactConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var NutshellSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $manager;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * NutshellCreateContactConnector constructor.
     *
     * @param NutshellSystem       $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $manager
     */
    public function __construct(NutshellSystem $system, DocumentManager $dm, CurlManagerInterface $manager)
    {
        $this->system                  = $system;
        $this->manager                 = $manager;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'nutshell-update-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data)
            || !isset($data['params']['contactId'])
            || !isset($data['params']['rev'])
            || !isset($data['params']['contact']['customFields'])
        ) {
            throw new CleverConnectorsException(
                'Missing data or required field _foreign_id or result_rev',
                CleverConnectorsException::MISSING_DATA
            );
        }

        /** @var string $eventType */
        $eventType     = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders());
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        $data['params']['contact']['customFields'] = [CleverCustomKeysEnum::getFromType($eventType) => 1];

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $requestDto->setBody(Json::encode($data))->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        try {
            $response = $this->manager->send($requestDto);

            $dto->setData($response->getBody());
        } catch (CurlException $e) {
            $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Nutshell has no support for event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

}