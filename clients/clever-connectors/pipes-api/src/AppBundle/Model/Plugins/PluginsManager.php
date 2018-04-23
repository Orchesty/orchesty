<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\ListConnector\CMCreateDistributionListConnector;
use CleverConnectors\AppBundle\Model\CM\ListConnector\CMGetDistributionsConnector;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Model\Systems\SystemTopologyRunner;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
     * @var SystemManager
     */
    private $manager;

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     * @var CMGetDistributionsConnector
     */
    private $getDistributionsConnector;

    /**
     * @var SystemTopologyRunner
     */
    private $systemTopologyRunner;

    /**
     * @var CMCreateDistributionListConnector
     */
    private $createDistributionListConnector;

    /**
     * OpenSourcePluginsManager constructor.
     *
     * @param DocumentManager                   $dm
     * @param SystemManager                     $manager
     * @param SystemLoader                      $loader
     * @param CMGetDistributionsConnector       $getDistributionsConnector
     * @param SystemTopologyRunner              $systemTopologyRunner
     * @param CMCreateDistributionListConnector $createDistributionListConnector
     */
    public function __construct(
        DocumentManager $dm,
        SystemManager $manager,
        SystemLoader $loader,
        CMGetDistributionsConnector $getDistributionsConnector,
        SystemTopologyRunner $systemTopologyRunner,
        CMCreateDistributionListConnector $createDistributionListConnector
    )
    {
        $this->dm                              = $dm;
        $this->manager                         = $manager;
        $this->loader                          = $loader;
        $this->getDistributionsConnector       = $getDistributionsConnector;
        $this->systemTopologyRunner            = $systemTopologyRunner;
        $this->createDistributionListConnector = $createDistributionListConnector;
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws SystemException
     * @throws CleverConnectorsException
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
            }
        } else {
            $systemInstall = $this->manager->installSystem($guid, $system, $token);
        }

        $systemInstall
            ->setPluginVersion(PluginHeadersEnum::get(PluginHeadersEnum::VERSION, $headers))
            ->setSettings([SystemInstall::SYSTEM_URL => $url]);

        $this->dm->flush();

        return $this->systemToArray($systemInstall, $this->getDistributionLists($request->headers->all()));
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws SystemException
     */
    public function uninstall(Request $request): array
    {
        $headers = $request->headers->all();

        $guid   = PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers);
        $system = PluginHeadersEnum::get(PluginHeadersEnum::SYSTEM, $headers);

        $this->manager->uninstallSystem($guid, $system);

        return [];
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
        $settings = $systemInstall->getSettings();
        $url      = $this->getUrl($request);

        if (!$systemInstall->getPluginVersion()) {
            $systemInstall->setPluginVersion(PluginHeadersEnum::get(PluginHeadersEnum::VERSION,
                $request->headers->all()));
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
     *
     * @throws Exception
     */
    public function createSubscriber(SystemInstall $systemInstall, Request $request): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::CREATED_SUBSCRIBERS, $request);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     *
     * @throws Exception
     */
    public function updateSubscriber(SystemInstall $systemInstall, Request $request): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::UPDATED_SUBSCRIBERS, $request);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     *
     * @throws Exception
     */
    public function deleteSubscriber(SystemInstall $systemInstall, Request $request): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::DELETED_SUBSCRIBERS, $request);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     *
     * @throws Exception
     */
    public function validateSubscriber(SystemInstall $systemInstall, Request $request): void
    {
        $this->startTopologies($systemInstall, TopologyNameUtils::VALIDATE_SUBSCRIBERS, $request);
    }

    /**
     * @param array $headers
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getDistributionLists(array $headers): array
    {
        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::GUID)  => PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers),
            CMHeaders::createKey(CMHeaders::TOKEN) => PluginHeadersEnum::get(PluginHeadersEnum::TOKEN, $headers),
        ]);

        return $this->getDistributionsConnector->getDistributionsArray($dto);
    }

    /**
     * @param array  $headers
     * @param string $body
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function createDistributionList(array $headers, string $body): array
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders([
                CMHeaders::createKey(CMHeaders::GUID)  => PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers),
                CMHeaders::createKey(CMHeaders::TOKEN) => PluginHeadersEnum::get(PluginHeadersEnum::TOKEN, $headers),
            ])
            ->setData($body);

        return $this->createDistributionListConnector->createList($dto);
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

        $this->systemTopologyRunner->runTopologies($topology, $systemInstall, $system, $request);
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throws SystemException
     */
    private function getUrl(Request $request): string
    {
        if (!$request->request->has(SystemInstall::REMOTE_HOST)) {
            throw new SystemException(sprintf('Missing parameter "%s" in body!', SystemInstall::REMOTE_HOST));
        }

        $host = $request->request->get(SystemInstall::REMOTE_HOST);
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
            SystemInstall::EVENT_SUBSCRIBE    => $systemInstall->isEventSubscribe(),
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