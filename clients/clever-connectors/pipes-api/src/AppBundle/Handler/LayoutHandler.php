<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Model\DataLayout\LayoutManager;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use Hanaboso\PipesFramework\Utils\ControllerUtils;

/**
 * Class LayoutHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class LayoutHandler
{

    /**
     * @var LayoutManager
     */
    private $layoutManager;

    /**
     * @var SystemManager
     */
    private $systemManager;

    /**
     * SystemHandler constructor.
     *
     * @param LayoutManager $layoutManager
     * @param SystemManager $systemManager
     */
    public function __construct(LayoutManager $layoutManager, SystemManager $systemManager)
    {
        $this->layoutManager = $layoutManager;
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

        ControllerUtils::checkParameters(['action', 'fields'], $data);

        $mapTemplate = $this->layoutManager->createDataLayout($systemInstall, $data);

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

        $mapTemplate = $this->layoutManager->updateDataLayout($this->layoutManager->get($id), $data);

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

        $this->layoutManager->deleteDataLayout($this->layoutManager->get($id));
    }

}