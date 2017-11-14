<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
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
     * OpenSourcePluginsManager constructor.
     *
     * @param DocumentManager $dm
     * @param StartingPoint   $startingPoint
     * @param SystemManager   $manager
     * @param SystemLoader    $loader
     */
    public function __construct(
        DocumentManager $dm,
        StartingPoint $startingPoint,
        SystemManager $manager,
        SystemLoader $loader
    )
    {
        $this->dm                 = $dm;
        $this->startingPoint      = $startingPoint;
        $this->manager            = $manager;
        $this->topologyRepository = $dm->getRepository(Topology::class);
        $this->loader             = $loader;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function install(Request $request): array
    {
        $url     = $this->getUrl($request);
        $headers = $request->headers->all();

        $system = $this->manager->installSystem(
            PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers),
            PluginHeadersEnum::get(PluginHeadersEnum::SYSTEM, $headers),
            PluginHeadersEnum::get(PluginHeadersEnum::TOKEN, $headers)
        );
        $system->setPluginVersion(PluginHeadersEnum::get(PluginHeadersEnum::VERSION, $headers))
            ->setSettings([
                SystemInstall::SYSTEM_URL => $url,
            ]);

        $this->dm->flush();

        return $this->systemToArray($system);
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
        $body = json_decode($request->getContent(), TRUE);
        $sett = $systemInstall->getSettings();

        if (!$systemInstall->getPluginVersion()) {
            $systemInstall->setPluginVersion($body[SystemInstall::PLUGIN_VERSION]);
        }

        $url = $this->getUrl($request);
        if (!array_key_exists(SystemInstall::SYSTEM_URL, $sett)) {
            $sett[SystemInstall::SYSTEM_URL] = $url;
        } else if ($sett[SystemInstall::SYSTEM_URL] !== $url) {
            throw new SystemException(
                sprintf('System url from request [%s] does not matched saved url in systemInstall [%s].',
                    $url, $sett[SystemInstall::SYSTEM_URL]
                ),
                SystemException::MISMATCH_URL
            );
        }

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
     */
    private function getUrl(Request $request): string
    {
        return rtrim('https://' . $request->getHost(), '/');
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function systemToArray(SystemInstall $systemInstall): array
    {
        $sett = $systemInstall->getSettings();

        return [
            SystemInstall::SYSTEM            => $systemInstall->getSystem(),
            SystemInstall::TOKEN             => $systemInstall->getToken(),
            SystemInstall::SYNCHRONIZED      => $systemInstall->isSynchronized(),
            SystemInstall::PLUGIN_VERSION    => $systemInstall->getPluginVersion(),
            SystemInstall::SYSTEM_URL        => $sett[SystemInstall::SYSTEM_URL],
            SystemInstall::EVENT_CREATE      => $systemInstall->isEventCreate(),
            SystemInstall::EVENT_UNSUBSCRIBE => $systemInstall->isEventUnsubscribe(),
            SystemInstall::EVENT_HARD_BOUNCE => $systemInstall->isEventHardBounce(),
        ];
    }

}