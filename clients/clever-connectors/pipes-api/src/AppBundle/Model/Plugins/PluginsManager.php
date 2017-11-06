<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
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
     * @var StartingPointHandler
     */
    private $handler;

    /**
     * OpenSourcePluginsManager constructor.
     *
     * @param DocumentManager      $dm
     * @param StartingPoint        $startingPoint
     * @param SystemManager        $manager
     * @param StartingPointHandler $handler
     */
    public function __construct(
        DocumentManager $dm,
        StartingPoint $startingPoint,
        SystemManager $manager,
        StartingPointHandler $handler
    )
    {
        $this->dm            = $dm;
        $this->startingPoint = $startingPoint;
        $this->manager       = $manager;
        $this->handler       = $handler;
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
        } else if ($systemInstall->getPluginVersion() !== $body[SystemInstall::PLUGIN_VERSION]) {
            throw new SystemException(
                sprintf('Version of installed system [%s] does not match plugin\'s version [%s].',
                    $systemInstall->getPluginVersion(), $body[SystemInstall::PLUGIN_VERSION]
                ),
                SystemException::MISMATCH_VERSION
            );
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
     * @param array         $data
     */
    public function createSubscriber(SystemInstall $systemInstall, array $data): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::CREATED_SUBSCRIBERS, $data);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     */
    public function updateSubscriber(SystemInstall $systemInstall, array $data): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::UPDATED_SUBSCRIBERS, $data);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     */
    public function deleteSubscriber(SystemInstall $systemInstall, array $data): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::DELETED_SUBSCRIBERS, $data);
    }

    /**
     * ------------------------------------------------ HELPERS ------------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     * @param string        $topology
     * @param array         $data
     */
    private function startTopologies(SystemInstall $systemInstall, string $topology, array $data): void
    {
        $topName = TopologyNameUtils::getTopologyName($topology,
            $systemInstall->getSystem());

        $topologies = $this->handler->getTopologies($topName);

        foreach ($topologies as $topology) {
            /** @var NodeRepository $repo */
            $repo = $this->dm->getRepository(Node::class);
            $node = $repo->getStartingNode($topology);

            $this->startingPoint->run($topology, $node, json_encode($data));
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function getUrl(Request $request): string
    {
        $url = $request->getUri();
        if ($request->getScheme() === 'http') {
            $url = 'https' . substr($url, 4);
        }

        return $url;
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
            'key'            => $systemInstall->getSystem(),
            'token'          => $systemInstall->getToken(),
            'synchronized'   => $systemInstall->isSynchronized(),
            'plugin_version' => $systemInstall->getPluginVersion(),
            'system_url'     => $sett[SystemInstall::SYSTEM_URL],
        ];
    }

}