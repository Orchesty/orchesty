<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;

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
     * @return array
     */
    public function getAllowedActions(): array
    {
        return [];
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
            'auth_type'   => $this->getAuthorizationType(),
        ];

        if ($systemInstall) {
            $arr['authorized']                     = $this->isAuthorized($systemInstall);
            $arr[SystemInstall::TOKEN]             = $systemInstall->getToken();
            $arr[SystemInstall::SYNCHRONIZED]      = $systemInstall->isSynchronized();
            $arr[SystemInstall::EVENT_CREATE]      = $systemInstall->isEventCreate();
            $arr[SystemInstall::EVENT_UNSUBSCRIBE] = $systemInstall->isEventUnsubscribe();
            $arr[SystemInstall::EVENT_HARD_BOUNCE] = $systemInstall->isEventHardBounce();
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