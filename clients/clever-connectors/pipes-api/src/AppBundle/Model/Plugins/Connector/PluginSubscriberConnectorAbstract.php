<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class PluginSubscriberConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
abstract class PluginSubscriberConnectorAbstract implements ConnectorInterface
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     * PluginSubscriberConnectorAbstract constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     * @param SystemLoader         $loader
     */
    function __construct(DocumentManager $dm, CurlManagerInterface $curl, SystemLoader $loader)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->curl                    = $curl;
        $this->loader                  = $loader;
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
            'Plugin has no support for event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $uri           = $this->getUri($systemInstall, $dto);

        $system = $this->loader->getSystem($systemInstall->getSystem());

        $reqDto = $system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $reqDto->setBody($this->getBody($dto))
            ->setUri($uri);

        $res = $this->curl->send($reqDto);

        if ($res->getStatusCode() !== 200) {
            throw new CleverConnectorsException(
                'Request to plugin failed | Server is unavailable.',
                CleverConnectorsException::REQUEST_FAILED
            );
        }

        return $dto->setData($res->getBody());
    }

    /**
     * @param ProcessDto $dto
     *
     * @return string
     * @throws CleverConnectorsException
     */
    protected function getIdFromDto(ProcessDto $dto): string
    {
        $body = json_decode($dto->getData(), TRUE);
        $id   = $body[CleverFieldsEnum::FOREIGN_ID] ?? '';

        if (empty($id)) {
            throw new CleverConnectorsException(
                'Missing id in data, PluginContactConnector',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $id;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return string
     */
    abstract protected function getBody(ProcessDto $dto): string;

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     *
     * @return Uri
     */
    abstract protected function getUri(SystemInstall $systemInstall, ProcessDto $dto): Uri;

}