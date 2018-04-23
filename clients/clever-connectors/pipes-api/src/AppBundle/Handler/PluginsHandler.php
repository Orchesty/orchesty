<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Plugins\PluginsManager;
use CleverConnectors\AppBundle\Model\Plugins\PluginsSecurityManager;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PluginsHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class PluginsHandler
{

    /**
     * @var PluginsManager
     */
    private $manager;

    /**
     * @var PluginsSecurityManager
     */
    private $security;

    /**
     * PluginsHandler constructor.
     *
     * @param PluginsManager         $manager
     * @param PluginsSecurityManager $security
     */
    public function __construct(PluginsManager $manager, PluginsSecurityManager $security)
    {
        $this->manager  = $manager;
        $this->security = $security;
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
        return $this->manager->install($request);
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws SystemException
     */
    public function uninstall(Request $request): array
    {
        return $this->manager->uninstall($request);
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function check(Request $request): array
    {
        return $this->manager->check($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     *
     * @throws CleverConnectorsException
     * @throws Exception
     */
    public function createSubscriber(Request $request): void
    {
        $this->addHeaderPrefix($request);

        $this->manager->createSubscriber($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     *
     * @throws CleverConnectorsException
     * @throws Exception
     */
    public function updateSubscriber(Request $request): void
    {
        $this->addHeaderPrefix($request);

        $this->manager->updateSubscriber($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     *
     * @throws CleverConnectorsException
     * @throws Exception
     */
    public function deleteSubscriber(Request $request): void
    {
        $this->addHeaderPrefix($request);

        $this->manager->deleteSubscriber($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     *
     * @throws CleverConnectorsException
     * @throws Exception
     */
    public function validateSubscriber(Request $request): void
    {
        $this->addHeaderPrefix($request);

        $this->manager->validateSubscriber($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getDistributionLists(Request $request): array
    {
        return $this->manager->getDistributionLists($request->headers->all());
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function createDistributionList(Request $request): array
    {
        return $this->manager->createDistributionList($request->headers->all(), $request->getContent());
    }

    /**
     * ----------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @param Request $request
     */
    private function addHeaderPrefix(Request $request): void
    {
        $this->prefixHeader(
            $request,
            PluginHeadersEnum::GUID,
            PluginHeadersEnum::toCMHeaders(PluginHeadersEnum::GUID)
        );
        $this->prefixHeader(
            $request,
            PluginHeadersEnum::TOKEN,
            PluginHeadersEnum::toCMHeaders(PluginHeadersEnum::TOKEN)
        );
        $this->prefixHeader(
            $request,
            PluginHeadersEnum::SYSTEM,
            PluginHeadersEnum::toCMHeaders(PluginHeadersEnum::SYSTEM)
        );
        $this->prefixHeader($request, PluginHeadersEnum::VERSION);
    }

    /**
     * @param Request     $request
     * @param string      $key
     * @param null|string $toKey
     */
    private function prefixHeader(Request $request, string $key, ?string $toKey = NULL): void
    {
        if ($request->headers->has($key)) {
            $request->headers->set(CMHeaders::createKey($toKey ?? $key), $request->headers->get($key));
            $request->headers->remove($key);
        }
    }

}