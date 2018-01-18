<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\ZapierSystem;
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

/**
 * Class ZapierCreateSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
class ZapierUpdateSubscriberConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * @var ZapierSystem
     */
    private $system;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * ZapierCreateSubscriberConnector constructor.
     *
     * @param ZapierSystem         $system
     * @param CurlManagerInterface $curl
     * @param DocumentManager      $dm
     */
    public function __construct(ZapierSystem $system, CurlManagerInterface $curl, DocumentManager $dm)
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->curl                    = $curl;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zapier-update-subscriber-connector';
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
            'Zapier has no support for event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $sett          = $systemInstall->getSettings();

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);

        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setBody($dto->getData())
            ->setUri(new Uri($sett[ZapierSystem::UPDATE_WEBHOOK_URL]));

        try {
            $res = $this->curl->send($requestDto);
        } catch (CurlException $e) {
            return $this->connectorError($e, $this->system, $systemInstall, $dto);
        }

        return $dto->setData($res->getBody());
    }

}