<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Traits\ZohoLoggerTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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

/**
 * Class ZohoGetContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
class ZohoGetContactConnector implements ConnectorInterface, LoggerAwareInterface
{

    use ZohoLoggerTrait;

    private const URL = '%s&id=%s';

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
     * ZohoGetContactConnector constructor.
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
        return 'zoho-get-contact-connector';
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

        if (!is_array($data) || !array_key_exists('id', $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field id',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url           = sprintf(self::URL, urldecode($requestDto->getUri(TRUE)), $data['id']);
        $requestDto
            ->setUri(new Uri(sprintf($url, 'getRecordById')))
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $response  = $this->manager->send($requestDto);
        $innerData = Json::decode($response->getBody(), TRUE);

        if (array_key_exists('error', $innerData['response'])) {
            $status = intval($innerData['response']['error']['code']) ?? 400;

            $this->connectorError($status, $this->system, $systemInstall, $dto);
        } else {

            if (!is_array($innerData) || !isset($innerData['response']['result']['Contacts']['row']['FL'][6]['content'])) {
                throw new CleverConnectorsException(
                    'Missing data or required field response_result_Contacts_row_FL_6_content',
                    CleverConnectorsException::MISSING_DATA
                );
            }

            $dto->setData(Json::encode($innerData['response']['result']['Contacts']['row']));
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
            'Zoho has no support for event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

}