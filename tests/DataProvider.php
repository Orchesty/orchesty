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
            'max'    => '2',
            'min'    => '2',
            'avg'    => '2.00',
            'total'  => '2',
            'errors' => '2',
        ];
    }

    /**
     * @return mixed[]
     */
    public static function dashboardData(): array
    {
        return [
            'process'       =>
                [
                    'activeTopologies'   => 1,
                    'disabledTopologies' => 2,
                    'totalRuns'          => 3,
                    'errorsCount'        => 4,
                    'successCount'       => 5,
                ],
            'errorLogs'     =>
                [
                    0 =>
                        [
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'level'        => 'ERROR',
                        ],
                    1 =>
                        [
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'level'        => 'ERROR',
                        ],
                ],
            'alertLogs'     =>
                [
                    0 =>
                        [
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'level'        => 'INFO',
                        ],
                    1 =>
                        [
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'level'        => 'INFO',
                        ],
                    2 =>
                        [
                            'time'         => 'date time',
                            'topologyId'   => '11ssd-ad25-a77',
                            'topologyName' => 'abc.v1',
                            'nodeId'       => '11ssd-ad25-a77',
                            'nodeName'     => 'abc.v1',
                            'level'        => 'INFO',
                        ],
                ],
            'systemMetrics' =>
                [
                    'cpu'            => 6.1,
                    'memory'         => 6.2,
                    'space'          => 7.2,
                    'tcpConnections' => 8,
                ],
            'filter'        =>
                [
                    'range' => '24h',
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
            'timestamp'     => 'date time',
            'topology_id'   => '11ssd-ad25-a77',
            'topology_name' => 'abc.v1',
            'node_id'       => '11ssd-ad25-a77',
            'node_name'     => 'abc.v1',
            'severity'      => $isError ? 'ERROR' : 'INFO',
            'message'       => 'Some message',
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
