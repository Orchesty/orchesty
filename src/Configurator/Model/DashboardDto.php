<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

/**
 * Class DashboardDto
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class DashboardDto
{

    private const TOPOLOGY_ID   = 'topology_id';
    private const TOPOLOGY_NAME = 'topology_name';
    private const NODE_ID       = 'node_id';
    private const NODE_NAME     = 'node_name';
    private const SEVERITY      = 'severity';
    private const MESSAGE       = 'message';
    private const TIMESTAMP     = 'timestamp';

    private const DEFAULT_SOURCE = 'System';

    /**
     * @var mixed[]
     */
    private array $errorLogs = [];

    /**
     * @var mixed[]
     */
    private array $alertLogs = [];

    //process
    private int $activeTopologies;
    private int $disabledTopologies;
    private int $totalRuns;
    private int $errorsCount;
    private int $successCount;
    private int $installedApps;

    //systemMetrics
    private float $cpu          = 0;
    private float $memory       = 0;
    private float $space        = 0;
    private int $tcpConnections = 0;

    //filter
    private string $range;

    /**
     * @param int $activeTopologies
     *
     * @return DashboardDto
     */
    public function setActiveTopologies(int $activeTopologies): self
    {
        $this->activeTopologies = $activeTopologies;

        return $this;
    }

    /**
     * @param int $disabledTopologies
     *
     * @return DashboardDto
     */
    public function setDisabledTopologies(int $disabledTopologies): self
    {
        $this->disabledTopologies = $disabledTopologies;

        return $this;
    }

    /**
     * @param int $totalRuns
     *
     * @return DashboardDto
     */
    public function setTotalRuns(int $totalRuns): self
    {
        $this->totalRuns = $totalRuns;

        return $this;
    }

    /**
     * @param int $errorsCount
     *
     * @return DashboardDto
     */
    public function setErrorsCount(int $errorsCount): self
    {
        $this->errorsCount = $errorsCount;

        return $this;
    }

    /**
     * @param int $installedApps
     *
     * @return $this
     */
    public function setInstalledApps(int $installedApps): self
    {
        $this->installedApps = $installedApps;

        return $this;
    }

    /**
     * @param int $successCount
     *
     * @return DashboardDto
     */
    public function setSuccessCount(int $successCount): self
    {
        $this->successCount = $successCount;

        return $this;
    }

    /**
     * @param float $cpu
     *
     * @return $this
     */
    public function setCpu(float $cpu): self
    {
        $this->cpu = $cpu;

        return $this;
    }

    /**
     * @param float $memory
     *
     * @return $this
     */
    public function setMemory(float $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * @param float $space
     *
     * @return $this
     */
    public function setSpace(float $space): self
    {
        $this->space = $space;

        return $this;
    }

    /**
     * @param int $tcpConnections
     *
     * @return DashboardDto
     */
    public function setTcpConnections(int $tcpConnections): self
    {
        $this->tcpConnections = $tcpConnections;

        return $this;
    }

    /**
     * @param string $range
     *
     * @return DashboardDto
     */
    public function setRange(string $range): self
    {
        $this->range = $range;

        return $this;
    }

    /**
     * @param string $time
     * @param string $topologyId
     * @param string $topologyName
     * @param string $nodeId
     * @param string $nodeName
     * @param string $level
     * @param string $message
     *
     * @return $this
     */
    public function addErrorLog(
        string $time,
        string $topologyId,
        string $topologyName,
        string $nodeId,
        string $nodeName,
        string $level,
        string $message,
    ): self
    {
        $this->errorLogs[] = [
            'level'        => $level,
            'message'      => $message,
            'nodeId'       => !empty($nodeId) ? $nodeId : self::DEFAULT_SOURCE,
            'nodeName'     => !empty($nodeName) ? $nodeName : self::DEFAULT_SOURCE,
            'time'         => $time,
            'topologyId'   => !empty($topologyId) ? $topologyId : self::DEFAULT_SOURCE,
            'topologyName' => !empty($topologyName) ? $topologyName : self::DEFAULT_SOURCE,
        ];

        return $this;
    }

    /**
     * @param string $time
     * @param string $topologyId
     * @param string $topologyName
     * @param string $nodeId
     * @param string $nodeName
     * @param string $level
     * @param string $message
     *
     * @return $this
     */
    public function addAlertLog(
        string $time,
        string $topologyId,
        string $topologyName,
        string $nodeId,
        string $nodeName,
        string $level,
        string $message,
    ): self
    {
        $this->alertLogs[] = [
            'level'        => $level,
            'message'      => $message,
            'nodeId'       => !empty($nodeId) ? $nodeId : self::DEFAULT_SOURCE,
            'nodeName'     => !empty($nodeName) ? $nodeName : self::DEFAULT_SOURCE,
            'time'         => $time,
            'topologyId'   => !empty($topologyId) ? $topologyId : self::DEFAULT_SOURCE,
            'topologyName' => !empty($topologyName) ? $topologyName : self::DEFAULT_SOURCE,
        ];

        return $this;
    }

    /**
     * @param mixed[] $errorLogs
     *
     * @return DashboardDto
     */
    public function setErrorLogs(array $errorLogs): self
    {
        foreach ($errorLogs as $errorLog) {
            $this->addErrorLog(
                $errorLog[self::TIMESTAMP],
                $errorLog[self::TOPOLOGY_ID],
                $errorLog[self::TOPOLOGY_NAME],
                $errorLog[self::NODE_ID],
                $errorLog[self::NODE_NAME],
                $errorLog[self::SEVERITY],
                $errorLog[self::MESSAGE],
            );
        }

        return $this;
    }

    /**
     * @param mixed[] $alertLogs
     *
     * @return DashboardDto
     */
    public function setAlertLogs(array $alertLogs): self
    {
        foreach ($alertLogs as $alertLog) {
            $this->addAlertLog(
                $alertLog[self::TIMESTAMP],
                $alertLog[self::TOPOLOGY_ID],
                $alertLog[self::TOPOLOGY_NAME],
                $alertLog[self::NODE_ID],
                $alertLog[self::NODE_NAME],
                $alertLog[self::SEVERITY],
                $alertLog[self::MESSAGE],
            );
        }

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'alertLogs'     => $this->alertLogs,
            'errorLogs'     => $this->errorLogs,
            'filter'        => [
                'range' => $this->range,
            ],
            'process'       => [
                'activeTopologies'   => $this->activeTopologies,
                'disabledTopologies' => $this->disabledTopologies,
                'errorsCount'        => $this->errorsCount,
                'installedApps'      => $this->installedApps,
                'successCount'       => $this->successCount,
                'totalRuns'          => $this->totalRuns,
            ],
            'systemMetrics' => [
                'cpu'            => $this->cpu,
                'memory'         => $this->memory,
                'space'          => $this->space,
                'tcpConnections' => $this->tcpConnections,
            ],
        ];
    }

}
