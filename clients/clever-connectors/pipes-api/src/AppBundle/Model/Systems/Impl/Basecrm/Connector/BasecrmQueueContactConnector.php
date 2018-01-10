<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\BasecrmSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\CronUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class BasecrmQueueContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
class BasecrmQueueContactConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var BasecrmSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * BasecrmQueueContactConnector constructor.
     *
     * @param BasecrmSystem        $system
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     */
    function __construct(BasecrmSystem $system, DocumentManager $dm, CurlManagerInterface $curl)
    {
        $this->system                  = $system;
        $this->curl                    = $curl;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'basecrm-queue-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('BaseCRM has no support for event, queueContactConnector.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     * @throws \CleverConnectors\AppBundle\Exceptions\CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = CronUtils::getSystemInstall($dto);

        if (empty($systemInstall->getSettings()[BasecrmSystem::QUE_ID]) ?? '') {
            $this->createQueue($dto, $systemInstall);
        }

        return $dto;
    }

    /**
     * @param ProcessDto    $processDto
     * @param SystemInstall $systemInstall
     *
     * @throws CurlException
     * @throws SystemException
     */
    private function createQueue(ProcessDto $processDto, SystemInstall $systemInstall): void
    {
        $uuid = uniqid();
        $dto  = $this->system->getRequestDtoNonSync($systemInstall, CurlManager::METHOD_POST);
        $dto->setDebugInfo(CMHeaders::debugInfo($processDto->getHeaders()))
            ->setHeaders(array_merge($dto->getHeaders(), [
                'X-Basecrm-Device-UUID' => $uuid,
            ]));
        $uri = new Uri(sprintf('%s/v2/sync/start', rtrim(BasecrmSystem::SYSTEM_URL, '/')));

        try {
            $res = $this->curl->send(RequestDto::from($dto, $uri));
        } catch (CurlException $e) {
            $this->logError($e->getResponse()->getStatusCode(), $this->system, $systemInstall);

            throw $e;
        }

        if (!in_array($res->getStatusCode(), [201, 204])) {
            throw new SystemException(sprintf('BaseCRM failed to create sync que, %s', $res->getBody()),
                SystemException::MISSING_RESPONSE_DATA);
        }

        $body = json_decode($res->getBody(), TRUE);
        if (!array_key_exists('data', $body)
            || !array_key_exists('id', $body['data'])
        ) {
            throw new SystemException(sprintf('BaseCRM failed to create sync que (missing id), %s', $res->getBody()),
                SystemException::MISSING_RESPONSE_DATA);
        }

        $sett                           = $systemInstall->getSettings();
        $sett[BasecrmSystem::SYNC_UUID] = $uuid;
        $sett[BasecrmSystem::QUE_ID]    = $body['data']['id'];
        $systemInstall->setSettings($sett);
        $this->systemInstallRepository->saveSystemInstall($systemInstall);

        $data                                                      = json_decode($processDto->getData(), TRUE);
        $data['system_install'][SystemInstall::ENCRYPTED_SETTINGS] = CryptManager::encrypt($systemInstall->getSettings());
        $processDto->setData(json_encode($data));
    }

}