<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\ZendeskSystem;
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class ZendeskUpdateUserConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
class ZendeskUpdateUserConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const SUB_URL = '/api/v2/users/%s.json';

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var ZendeskSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * ZendeskCreateUserConnector constructor.
     *
     * @param ZendeskSystem        $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     */
    function __construct(ZendeskSystem $system, DocumentManager $dm, CurlManagerInterface $curl)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
        $this->curl                    = $curl;
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zendesk-update-user-connector';
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
            'ProcessEvent is not implemented, Zendesk updateUserConnector.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws SystemException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $data          = json_decode($dto->getData(), TRUE);

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_PUT);
        $uri        = new Uri(sprintf(rtrim($requestDto->getUri(TRUE), '/') . self::SUB_URL, $data['id']));

        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setUri($uri)
            ->setBody($data['body']);

        try {
            $res = $this->curl->send($requestDto);

            $data  = json_decode($res->getBody(), TRUE);
            $field = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders()) ?? '';

            if (!array_key_exists('user', $data)
                || !array_key_exists('user_fields', $data['user'])
                || !array_key_exists(CleverCustomKeysEnum::getFromType($field), $data['user']['user_fields'])
            ) {
                $this->logError(400, $this->system, $systemInstall);

                throw new CleverConnectorsException('CM field does not exist, Zendesk updateUserConnector.',
                    CleverConnectorsException::MISSING_DATA);
            }

            $dto->setData($res->getBody());
        } catch (CurlException $e) {
            $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        return $dto;
    }

}