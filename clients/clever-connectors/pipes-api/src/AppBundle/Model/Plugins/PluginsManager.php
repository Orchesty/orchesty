<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\CM\ListConnector\CMGetDistributionsConnector;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PluginsManager
 *
 * @package CleverConnectors\AppBundle\Model\Plugins
 */
class PluginsManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var StartingPoint
     */
    private $startingPoint;

    /**
     * @var SystemManager
     */
    private $manager;

    /**
     * @var TopologyRepository|ObjectRepository
     */
    private $topologyRepository;

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     * @var CMGetDistributionsConnector
     */
    private $distConn;

    /**
     * OpenSourcePluginsManager constructor.
     *
     * @param DocumentManager             $dm
     * @param StartingPoint               $startingPoint
     * @param SystemManager               $manager
     * @param SystemLoader                $loader
     * @param CMGetDistributionsConnector $distConn
     */
    public function __construct(
        DocumentManager $dm,
        StartingPoint $startingPoint,
        SystemManager $manager,
        SystemLoader $loader,
        CMGetDistributionsConnector $distConn
    )
    {
        $this->dm                 = $dm;
        $this->startingPoint      = $startingPoint;
        $this->manager            = $manager;
        $this->topologyRepository = $dm->getRepository(Topology::class);
        $this->loader             = $loader;
        $this->distConn           = $distConn;
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws SystemException
     */
    public function install(Request $request): array
    {
        $url     = $this->getUrl($request);
        $headers = $request->headers->all();

        $guid   = PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers);
        $system = PluginHeadersEnum::get(PluginHeadersEnum::SYSTEM, $headers);
        $token  = PluginHeadersEnum::get(PluginHeadersEnum::TOKEN, $headers);

        $systemInstall = $this->manager->getSystemInstallOrNull($guid, $system);

        if ($systemInstall) {
            $settings = $systemInstall->getSettings();
            if ($systemInstall->getToken() !== $token) {
                $systemInstall->setToken($token);
            } elseif ($this->checkUrl($url, $settings) === FALSE) {
                throw new SystemException(
                    'This connector is already in use. Uninstall the connector and install it again with the new location.',
                    SystemException::MISMATCH_URL
                );
            } else {
                return $this->systemToArray($systemInstall, $this->getDistributionLists($request->headers->all()));
            }
        } else {
            $systemInstall = $this->manager->installSystem($guid, $system, $token);
            $systemInstall
                ->setPluginVersion(PluginHeadersEnum::get(PluginHeadersEnum::VERSION, $headers))
                ->setSettings([SystemInstall::SYSTEM_URL => $url]);
        }

        $this->dm->flush();

        return $this->systemToArray($systemInstall, $this->getDistributionLists($request->headers->all()));
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     *
     * @return array
     * @throws SystemException
     */
    public function check(SystemInstall $systemInstall, Request $request): array
    {
        $body     = json_decode($request->getContent(), TRUE);
        $settings = $systemInstall->getSettings();
        $url      = $this->getUrl($request);

        if (!$systemInstall->getPluginVersion()) {
            $systemInstall->setPluginVersion($body[SystemInstall::PLUGIN_VERSION]);
        }

        if ($this->checkUrl($url, $settings) === FALSE) {
            throw new SystemException(
                sprintf('System url from request [%s] does not matched saved url in systemInstall [%s].',
                    $this->getUrl($request), $systemInstall->getSettings()[SystemInstall::SYSTEM_URL]
                ),
                SystemException::MISMATCH_URL
            );
        }

        $settings[SystemInstall::SYSTEM_URL] = $url;
        $systemInstall->setSettings($settings);
        $this->dm->flush();

        return $this->systemToArray($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     */
    public function createSubscriber(SystemInstall $systemInstall, Request $request): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::CREATED_SUBSCRIBERS, $request);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     */
    public function updateSubscriber(SystemInstall $systemInstall, Request $request): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::UPDATED_SUBSCRIBERS, $request);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     */
    public function deleteSubscriber(SystemInstall $systemInstall, Request $request): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::DELETED_SUBSCRIBERS, $request);
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    public function getDistributionLists(array $headers): array
    {
        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::GUID)  => PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers),
            CMHeaders::createKey(CMHeaders::TOKEN) => PluginHeadersEnum::get(PluginHeadersEnum::TOKEN, $headers),
        ]);

        return $this->distConn->getDistributionsArray($dto);
    }

    /**
     * ------------------------------------------------ HELPERS ------------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     * @param string        $topology
     * @param Request       $request
     *
     * @throws Exception
     */
    private function startTopologies(SystemInstall $systemInstall, string $topology, Request $request): void
    {
        $system = $this->loader->getSystem($systemInstall->getSystem());

        $topologies = $this->topologyRepository->getRunnableTopologies(
            TopologyNameUtils::getTopologyName(
                $topology,
                $systemInstall->getSystem(),
                $systemInstall->getUser()
            )
        );

        if (empty($topologies)) {
            $name       = $system->getCustomTopologyName(
                TopologyNameUtils::getTopologyName($topology, $systemInstall->getSystem())
            );
            $topologies = $this->topologyRepository->getRunnableTopologies($name);

            if (empty($topologies)) {
                throw new Exception(sprintf('No topology with name [%s] has been found.', $name));
            }
        }

        foreach ($topologies as $topology) {
            /** @var NodeRepository $repo */
            $repo = $this->dm->getRepository(Node::class);
            $node = $repo->getStartingNode($topology);

            $this->startingPoint->runWithRequest($request, $topology, $node);
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throws SystemException
     */
    private function getUrl(Request $request): string
    {
        if (!$request->request->has('remote_host')) {
            throw new SystemException('Missing parameter "remote_host" in body!');
        }

        $host = $request->request->get('remote_host');
        $host = preg_replace('#^https?://#', '', rtrim($host, '/'));

        return sprintf('https://%s', $host);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $distLists
     *
     * @return array
     */
    private function systemToArray(SystemInstall $systemInstall, array $distLists = []): array
    {
        $sett = $systemInstall->getSettings();

        return [
            SystemInstall::SYSTEM             => $systemInstall->getSystem(),
            SystemInstall::TOKEN              => $systemInstall->getToken(),
            SystemInstall::SYNCHRONIZED       => $systemInstall->isSynchronized(),
            SystemInstall::PLUGIN_VERSION     => $systemInstall->getPluginVersion(),
            SystemInstall::SYSTEM_URL         => $sett[SystemInstall::SYSTEM_URL],
            SystemInstall::EVENT_CREATE       => $systemInstall->isEventCreate(),
            SystemInstall::EVENT_UNSUBSCRIBE  => $systemInstall->isEventUnsubscribe(),
            SystemInstall::EVENT_HARD_BOUNCE  => $systemInstall->isEventHardBounce(),
            SystemInstall::DISTRIBUTION_LISTS => $distLists,
        ];
    }

    /**
     * @param string $url
     * @param array  $settings
     *
     * @return bool
     */
    private function checkUrl(string $url, array $settings): bool
    {
        return !(array_key_exists(SystemInstall::SYSTEM_URL, $settings) &&
            $settings[SystemInstall::SYSTEM_URL] !== $url);
    }

}