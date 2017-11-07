<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\Plugins\PluginsManager;
use CleverConnectors\AppBundle\Model\Plugins\PluginsSecurityManager;
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
     * @param array $data
     */
    public function createSubscriber(array $data): void
    {
        $this->manager->createSubscriber($this->security->getSystemInstall(), $data);
    }

    /**
     * @param array $data
     */
    public function updateSubscriber(array $data): void
    {
        $this->manager->updateSubscriber($this->security->getSystemInstall(), $data);
    }

    /**
     * @param array $data
     */
    public function deleteSubscriber(array $data): void
    {
        $this->manager->deleteSubscriber($this->security->getSystemInstall(), $data);
    }

}