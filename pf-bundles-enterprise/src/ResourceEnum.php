<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise;

use Hanaboso\AclBundle\Enum\ResourceEnum as AclResourceEnum;
use Hanaboso\Utils\Exception\EnumException;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\PipesFrameworkEnterprise
 */
final class ResourceEnum extends AclResourceEnum
{

    public const string APPLICATION      = 'application';
    public const string CONNECTOR        = 'connector';
    public const string LIMITER          = 'limiter';
    public const string LOGS             = 'logs';
    public const string OVERVIEW         = 'overview';
    public const string PROCESS          = 'process';
    public const string SCHEDULED_TASK   = 'scheduled_task';
    public const string SETTINGS         = 'settings';
    public const string TOPOLOGY         = 'topology';
    public const string TOPOLOGY_LOG     = 'topology_log';
    public const string TOPOLOGY_METRICS = 'topology_metrics';
    public const string TOPOLOGY_PROCESS = 'topology_process';
    public const string TOPOLOGY_TRASH   = 'topology_trash';
    public const string USER_TASK        = 'user_task';

    public const array TOPOLOGY_SCOPED_PREFIXES = [
        self::TOPOLOGY_LOG,
        self::TOPOLOGY_METRICS,
        self::TOPOLOGY_PROCESS,
        self::TOPOLOGY_TRASH,
    ];

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::APPLICATION      => 'Application',
        self::CONNECTOR        => 'Connector',
        self::FILE             => 'File',
        self::GROUP            => 'Group entity',
        self::LIMITER          => 'Limiter',
        self::LOGS             => 'Logs',
        self::OVERVIEW         => 'Overview',
        self::PROCESS          => 'Process',
        self::RULE             => 'Rule',
        self::SCHEDULED_TASK   => 'Scheduled task',
        self::SETTINGS         => 'Settings',
        self::TMP_USER         => 'TmpUser entity',
        self::TOKEN            => 'Token entity',
        self::TOPOLOGY         => 'Topology',
        self::TOPOLOGY_LOG     => 'Topology log',
        self::TOPOLOGY_METRICS => 'Topology metrics',
        self::TOPOLOGY_PROCESS => 'Topology process',
        self::TOPOLOGY_TRASH   => 'Topology trash',
        self::USER             => 'User entity',
        self::USER_TASK        => 'User task',
    ];

    /**
     * @param string $val
     *
     * @return string
     * @throws EnumException
     */
    public static function isValid(string $val): string
    {
        try {
            return parent::isValid($val);
        } catch (EnumException $e) {
            foreach (self::TOPOLOGY_SCOPED_PREFIXES as $prefix) {
                if (str_starts_with($val, sprintf('%s:', $prefix))) {
                    return $val;
                }
            }

            throw $e;
        }
    }

}
