<?php declare(strict_types=1);

namespace PipesFrameworkTests;

/**
 * Class DataProvider
 *
 * @package PipesFrameworkTests
 */
final class DataProvider
{

    /**
     * @return string[]
     */
    public static function topologiesProcessTimeMetrics(): array
    {
        return [
            'avg'    => '2.00',
            'errors' => '2',
            'max'    => '2',
            'min'    => '2',
            'total'  => '2',
        ];
    }

    /**
     * @return mixed[]
     */
    public static function dashboardData(): array
    {
        return [
            'alertLogs'     =>
                [
                    0 =>
                        [
                            'level'        => 'INFO',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                        ],
                    1 =>
                        [
                            'level'        => 'INFO',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                        ],
                    2 =>
                        [
                            'level'        => 'INFO',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                        ],
                ],
            'errorLogs'     =>
                [
                    0 =>
                        [
                            'level'        => 'ERROR',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                        ],
                    1 =>
                        [
                            'level'        => 'ERROR',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                        ],
                ],
            'filter'        =>
                [
                    'range' => '24h',
                ],
            'process'       =>
                [
                    'activeTopologies'   => 1,
                    'disabledTopologies' => 2,
                    'errorsCount'        => 4,
                    'successCount'       => 5,
                    'totalRuns'          => 3,
                ],
            'systemMetrics' =>
                [
                    'cpu'            => 6.1,
                    'memory'         => 6.2,
                    'space'          => 7.2,
                    'tcpConnections' => 8,
                ],
        ];
    }

    /**
     * @param bool $isError
     *
     * @return string[]
     */
    public static function dashboardLog(bool $isError = FALSE): array
    {
        return [
            'message'       => 'Some message',
            'node_id'       => '11ssd-ad25-a77',
            'node_name'     => 'abc.v1',
            'severity'      => $isError ? 'ERROR' : 'INFO',
            'timestamp'     => 'date time',
            'topology_id'   => '11ssd-ad25-a77',
            'topology_name' => 'abc.v1',
        ];
    }

    /**
     * @param int  $items
     * @param bool $isError
     *
     * @return mixed[]
     */
    public static function dashboardLogs(int $items, bool $isError = FALSE): array
    {
        $res = [];
        for ($i = 1; $i <= $items; $i++) {
            $res[] = self::dashboardLog($isError);
        }

        return $res;
    }

    /**
     * @param mixed[] $items
     *
     * @return mixed[][]
     */
    public static function filter(array $items): array
    {
        return [
            'items' => $items,
        ];
    }

}
