<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class PluginSwitchTokenConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginSwitchTokenConnector implements ConnectorInterface
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
     * PluginSwitchTokenConnector constructor.
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
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-switch-token';
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
     * @throws \CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        /** @var PluginSystemAbstract $system */
        $system    = $this->loader->getSystem($systemInstall->getSystem());
        $params    = [
            'body' => $dto->getData(),
            'uri'  => $system->createUri($systemInstall, $system->getSwitchTokenUrl()),
        ];
        $requester = $system->getSwitchTokenRequester($systemInstall);
        $req       = $requester->getRequestDto($params);
        $req->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $res = $this->curl->send($req);

        $requester->processResponse($res, $systemInstall);

        return $dto->setData($res->getBody());
    }

}