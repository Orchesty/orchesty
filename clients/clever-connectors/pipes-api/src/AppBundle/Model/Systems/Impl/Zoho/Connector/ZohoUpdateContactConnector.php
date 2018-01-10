<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\LoggerUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class ZohoUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
class ZohoUpdateContactConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    private const URL = '%s&id=%s&newFormat=1&xmlData=%s';

    /**
     * @var ZohoSystem
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
     * ZohoCreateContactConnector constructor.
     *
     * @param ZohoSystem           $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $manager
     */
    public function __construct(ZohoSystem $system, DocumentManager $dm, CurlManagerInterface $manager)
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->manager                 = $manager;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zoho-update-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {

        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists(CleverFieldsEnum::FOREIGN_ID, $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field _foreign_id',
                CleverConnectorsException::MISSING_DATA
            );
        }

        /** @var string $eventType */
        $eventType     = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders());
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $url           = sprintf(
            self::URL,
            urldecode($requestDto->getUri(TRUE)),
            $data[CleverFieldsEnum::FOREIGN_ID],
            sprintf(
                '<Contacts><row no="1"><FL val="%s">1</FL></row></Contacts>',
                CleverCustomKeysEnum::getFromType($eventType)
            )
        );
        $requestDto
            ->setUri(new Uri(sprintf($url, 'updateRecords')))
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        try {
            $response = $this->manager->send($requestDto);
        } catch (CurlException $exception) {
            $response = $exception->getResponse();
            if ($response->getStatusCode() == 500) {
                $this->logger->info(
                    NotificationTypeEnum::SERVICE_UNAVAILABLE,
                    LoggerUtils::getMessage($this->system, $systemInstall)
                );
            }
            throw $exception;
        }

        return $dto->setData($response->getBody());
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Zoho has no support for event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

}