<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\BasecrmSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class BasecrmAcknowledgeContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
class BasecrmAcknowledgeContactConnector implements ConnectorInterface
{

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var BasecrmSystem
     */
    private $system;
    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * BasecrmContactMapperAbstract constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param BasecrmSystem        $system
     * @param DocumentManager      $dm
     */
    function __construct(CurlManagerInterface $curlManager, BasecrmSystem $system, DocumentManager $dm)
    {
        $this->curlManager             = $curlManager;
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'basecrm-acknowledge-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException('BaseCRM ackConnector has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $item = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('meta', $item)
            || !array_key_exists('type', $item['meta'])
            || !array_key_exists('sync', $item['meta'])
            || !array_key_exists('ack_key', $item['meta']['sync'])
            || !array_key_exists('event_type', $item['meta']['sync'])
            || !array_key_exists('ack_key', $item['meta']['sync'])
        ) {
            throw new CleverConnectorsException(
                'Response data is malformed or missing from BaseCRM syncApi.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if ($item['meta']['type'] !== 'contact') {
            $headers = [
                PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 1003,
                PipesHeaders::createKey(PipesHeaders::RESULT_STATUS)  => 'DO_NOT_CONTINUE',
                PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => 'Received item update in not for [Contact] entity, BaseCRM.',
                PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => '',
            ];

            $dto->setHeaders(array_merge($dto->getHeaders(), $headers));
        } else {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
            $requestDto->setBody(json_encode([
                'data' => [
                    'ack_keys' => [
                        $item['meta']['sync']['ack_key'],
                    ],
                ],
            ]));

            $url = new Uri(sprintf('%s/v2/sync/ack', rtrim($requestDto->getUri(TRUE), '/')));
            $requestDto->setUri($url);

            $this->curlManager->send($requestDto);
        }

        return $dto;
    }

}