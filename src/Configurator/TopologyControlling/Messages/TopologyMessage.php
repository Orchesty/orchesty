<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 12.12.17
 * Time: 14:06
 */

namespace Hanaboso\PipesFramework\Configurator\TopologyControlling\Messages;

/**
 * Class TopologyMessage
 *
 * @package Hanaboso\PipesFramework\Configurator\TopologyControlling\Messages
 */
class TopologyMessage
{

    public const STOP   = 'stop';
    public const DELETE = 'delete';

    /**
     * @var string
     */
    protected $topologyId;

    /**
     * @var string
     */
    protected $action;

    /**
     * TopologyMessage constructor.
     *
     * @param string $topologyId
     * @param string $action
     */
    public function __construct(string $topologyId, string $action = self::STOP)
    {
        $this->topologyId = $topologyId;
        $this->action     = $action;
    }

    /**
     * @return string
     */
    public function getTopologyId(): string
    {
        return $this->topologyId;
    }

    /**
     * @return array
     */
    public function getMessage(): array
    {
        return [
            'action'     => $this->getAction(),
            'topologyId' => $this->getTopologyId(),
        ];
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

}
