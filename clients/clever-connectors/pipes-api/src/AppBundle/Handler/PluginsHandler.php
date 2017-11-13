<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\Plugins\PluginsManager;
use CleverConnectors\AppBundle\Model\Plugins\PluginsSecurityManager;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
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
     * @var SystemHandler
     */
    private $handler;

    /**
     * PluginsHandler constructor.
     *
     * @param PluginsManager         $manager
     * @param PluginsSecurityManager $security
     * @param SystemHandler          $handler
     */
    public function __construct(PluginsManager $manager, PluginsSecurityManager $security, SystemHandler $handler)
    {
        $this->manager  = $manager;
        $this->security = $security;
        $this->handler  = $handler;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function install(Request $request): array
    {
        $headers = $request->headers->all();
        $this->handler->isSystemInstalled(
            PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers),
            PluginHeadersEnum::get(PluginHeadersEnum::SYSTEM, $headers)
        );

        return $this->manager->install($request);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function check(Request $request): array
    {
        return $this->manager->check($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     */
    public function createSubscriber(Request $request): void
    {
        $this->addHeaderPrefix($request);

        $this->manager->createSubscriber($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     */
    public function updateSubscriber(Request $request): void
    {
        $this->addHeaderPrefix($request);

        $this->manager->updateSubscriber($this->security->getSystemInstall(), $request);
    }

    /**
     * @param Request $request
     */
    public function deleteSubscriber(Request $request): void
    {
        $this->addHeaderPrefix($request);

        $this->manager->deleteSubscriber($this->security->getSystemInstall(), $request);
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
            $request->headers->set(PipesHeaders::PF_PREFIX . $toKey ?? $key,
                $request->headers->get($key));
            $request->headers->remove($key);
        }
    }

}