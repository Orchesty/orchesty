<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;

/**
 * Class SystemTrait
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Traits
 */
trait SystemTrait
{

    /**
     * @var array
     */
    protected $topologyNames = [];

    /**
     * @var ActionDto[]
     */
    protected $allowedActions = [];

    /**
     * @param string $name
     *
     * @return string
     */
    public function getCustomTopologyName(string $name): string
    {
        if (array_key_exists($name, $this->topologyNames)) {
            return $this->topologyNames[$name];
        }

        return $name;
    }

    /**
     * @return bool
     */
    public function isDynamicMapper(): bool
    {
        return FALSE;
    }

    /**
     * @return ActionDto[]
     */
    public function getAllowedActions(): array
    {
        return $this->allowedActions;
    }

    /**
     * @return array
     */
    public function getAllowedActionsArray(): array
    {
        $actions = [];
        if ($this->allowedActions) {
            foreach ($this->allowedActions as $allowedAction) {
                $actions[] = $allowedAction->getAction();
            }
        }

        return $actions;
    }

    /**
     * @param ActionDto $dto
     */
    public function addAllowedAction(ActionDto $dto): void
    {
        $this->allowedActions[$dto->getAction()] = $dto;
    }

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array
    {
        $arr = [
            'key'         => $this->getKey(),
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
            'type'        => $this->getType(),
            'ui_type'     => $this->getUIType(),
            'auth_type'   => $this->getAuthorizationType(),
        ];

        if ($systemInstall) {
            $arr['authorized']                     = $this->isAuthorized($systemInstall);
            $arr[SystemInstall::TOKEN]             = $systemInstall->getToken();
            $arr[SystemInstall::SYNCHRONIZED]      = $systemInstall->isSynchronized();
            $arr[SystemInstall::EVENT_CREATE]      = $systemInstall->isEventCreate();
            $arr[SystemInstall::EVENT_UNSUBSCRIBE] = $systemInstall->isEventUnsubscribe();
            $arr[SystemInstall::EVENT_HARD_BOUNCE] = $systemInstall->isEventHardBounce();
            $arr[SystemInstall::EVENT_SUBSCRIBE]   = $systemInstall->isEventSubscribe();
        }

        return $arr;
    }

    /**
     * @param string $key
     * @param array  $settings
     *
     * @return bool|mixed|null
     */
    protected function prepareValue(string $key, array $settings)
    {
        if (isset($settings[$key])) {
            return $settings[$key];
        }

        return NULL;
    }

}