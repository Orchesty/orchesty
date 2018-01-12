<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\ZohoSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
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
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class ZohoCreateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
class ZohoCreateContactConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const URL = '%s&newFormat=1&xmlData=%s';

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
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zoho-create-contact-connector';
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

        if (!is_array($data) || !array_key_exists('xml', $data) || !Strings::contains($data['xml'], 'Email')) {
            throw new CleverConnectorsException(
                'Missing data or required field email',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $url           = sprintf(self::URL, urldecode($requestDto->getUri(TRUE)), $data['xml']);
        $requestDto
            ->setUri(new Uri(sprintf($url, 'insertRecords')))
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        try {
            $response = $this->manager->send($requestDto);
        } catch (CurlException $exception) {
            $response = $exception->getResponse();
            if ($response->getStatusCode() == 500) {
                $this->logError($response->getStatusCode(), $this->system, $systemInstall);
            }
            throw $exception;
        }
        $innerData = Json::decode($response->getBody(), TRUE);

        if (!is_array($innerData) || !isset($innerData['response']['result']['recorddetail']['FL'][0]['content'])) {
            throw new CleverConnectorsException(
                'Missing data or required field response_result_recorddetail_FL_0_content',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(Json::encode(['id' => $innerData['response']['result']['recorddetail']['FL'][0]['content']]));
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