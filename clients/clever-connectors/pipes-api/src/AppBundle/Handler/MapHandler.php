<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Model\MapTemplate\MapManager;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use Hanaboso\PipesFramework\Commons\Utils\ControllerUtils;

/**
 * Class MapHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class MapHandler
{

    /**
     * @var MapManager
     */
    private $mapMmanager;

    /**
     * @var SystemManager
     */
    private $systemManager;

    /**
     * SystemHandler constructor.
     *
     * @param MapManager    $mapManager
     * @param SystemManager $systemManager
     */
    public function __construct(MapManager $mapManager, SystemManager $systemManager)
    {
        $this->mapMmanager   = $mapManager;
        $this->systemManager = $systemManager;
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     */
    public function create(string $user, string $system, array $data): array
    {
        $systemInstall = $this->systemManager->getSystemInstall($user, $system);

        ControllerUtils::checkParameters(['action', 'direction', 'fields'], $data);

        $mapTemplate = $this->mapMmanager->create($systemInstall, $data);

        return $mapTemplate->toArray();
    }

    /**
     * @param string $id
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     */
    public function update(string $id, string $user, string $system, array $data): array
    {
        $this->systemManager->getSystemInstall($user, $system);

        ControllerUtils::checkParameters(['fields'], $data);

        $mapTemplate = $this->mapMmanager->update($this->mapMmanager->get($id), $data);

        return $mapTemplate->toArray();
    }

    /**
     * @param string $id
     * @param string $user
     * @param string $system
     */
    public function delete(string $id, string $user, string $system): void
    {
        $this->systemManager->getSystemInstall($user, $system);

        $this->mapMmanager->delete($this->mapMmanager->get($id));
    }

}