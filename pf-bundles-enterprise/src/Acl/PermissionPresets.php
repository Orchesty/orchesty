<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Acl;

use Hanaboso\PipesFrameworkEnterprise\Acl\Enum\ActionEnum;
use Hanaboso\PipesFrameworkEnterprise\ResourceEnum;
use InvalidArgumentException;

/**
 * Class PermissionPresets
 *
 * Hierarchical permission presets. Each higher level includes all rules from the level below.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Acl
 */
final class PermissionPresets
{

    public const string CHAT_USER          = 'chat_user';
    public const string MONITORING         = 'monitoring';
    public const string PROCESS_MANAGEMENT = 'process_management';
    public const string DEVELOPER          = 'developer';
    public const string SYSTEM_MANAGER     = 'system_manager';
    public const string SUPER_ADMIN        = 'super_admin';

    private const array PRESET_META = [
        self::CHAT_USER => [
            'description' => 'Access to Trace chat only. Topology run permissions are assigned via access groups.',
            'label'       => 'Chat User',
            'level'       => 5,
        ],
        self::DEVELOPER => [
            'description' => 'Process Management + full topology editing, application management.',
            'label'       => 'Developer',
            'level'       => 2,
        ],
        self::MONITORING => [
            'description' => 'Read-only access to dashboard, topologies, scheduled tasks, processes, logs and failed messages.',
            'label'       => 'Monitoring',
            'level'       => 4,
        ],
        self::PROCESS_MANAGEMENT => [
            'description' => 'Monitoring + manage scheduled tasks, run/enable topologies, reprocess failed messages.',
            'label'       => 'Process Management',
            'level'       => 3,
        ],
        self::SUPER_ADMIN => [
            'description' => 'Full access including user and group management.',
            'label'       => 'Super Admin',
            'level'       => 0,
        ],
        self::SYSTEM_MANAGER => [
            'description' => 'Developer + settings, SDKs, API tokens.',
            'label'       => 'System Manager',
            'level'       => 1,
        ],
    ];

    private const array PRESET_RULES = [
        self::CHAT_USER => [
            ResourceEnum::TRACE => [ActionEnum::READ],
        ],
        self::DEVELOPER => [
            ResourceEnum::APPLICATION => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE],
            ResourceEnum::TOPOLOGY    => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE, ActionEnum::RUN],
        ],
        self::MONITORING => [
            ResourceEnum::CONNECTOR      => [ActionEnum::READ],
            ResourceEnum::LIMITER        => [ActionEnum::READ],
            ResourceEnum::LOGS           => [ActionEnum::READ],
            ResourceEnum::OVERVIEW       => [ActionEnum::READ],
            ResourceEnum::PROCESS        => [ActionEnum::READ],
            ResourceEnum::SCHEDULED_TASK => [ActionEnum::READ],
            ResourceEnum::TOPOLOGY       => [ActionEnum::READ],
            ResourceEnum::USER_TASK      => [ActionEnum::READ],
        ],
        self::PROCESS_MANAGEMENT => [
            ResourceEnum::SCHEDULED_TASK => [ActionEnum::READ, ActionEnum::WRITE],
            ResourceEnum::TOPOLOGY       => [ActionEnum::READ, ActionEnum::RUN],
            ResourceEnum::USER_TASK      => [ActionEnum::READ, ActionEnum::WRITE],
        ],
        self::SUPER_ADMIN => [
            ResourceEnum::USER => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE],
        ],
        self::SYSTEM_MANAGER => [
            ResourceEnum::SETTINGS => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE],
        ],
    ];

    private const array HIERARCHY = [
        self::CHAT_USER,
        self::MONITORING,
        self::PROCESS_MANAGEMENT,
        self::DEVELOPER,
        self::SYSTEM_MANAGER,
        self::SUPER_ADMIN,
    ];

    /**
     * @param string $preset
     *
     * @return array<string, string[]>
     */
    public static function resolve(string $preset): array
    {
        $idx = array_search($preset, self::HIERARCHY, TRUE);

        if ($idx === FALSE) {
            throw new InvalidArgumentException(sprintf('Unknown preset [%s].', $preset));
        }

        $merged = [];

        for ($i = 0; $i <= $idx; $i++) {
            foreach (self::PRESET_RULES[self::HIERARCHY[$i]] as $resource => $actions) {
                $merged[$resource] = $actions;
            }
        }

        return $merged;
    }

    /**
     * @return array<string, array{name: string, label: string, description: string, level: int, rules: array<string, string[]>}>
     */
    public static function all(): array
    {
        $result = [];

        foreach (self::HIERARCHY as $name) {
            $meta          = self::PRESET_META[$name];
            $result[$name] = [
                'description' => $meta['description'],
                'label'       => $meta['label'],
                'level'       => $meta['level'],
                'name'        => $name,
                'rules'       => self::resolve($name),
            ];
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public static function names(): array
    {
        return self::HIERARCHY;
    }

    /**
     * @param string $preset
     *
     * @return int
     */
    public static function getLevel(string $preset): int
    {
        if (!isset(self::PRESET_META[$preset])) {
            throw new InvalidArgumentException(sprintf('Unknown preset [%s].', $preset));
        }

        return self::PRESET_META[$preset]['level'];
    }

    /**
     * Detect which preset (if any) matches a group's current rules.
     *
     * @param array<string, string[]> $groupRules resource => actions[]
     *
     * @return string|null
     */
    public static function detect(array $groupRules): ?string
    {
        $found = NULL;

        foreach (array_reverse(self::HIERARCHY) as $name) {
            $presetRules = self::resolve($name);

            if (self::rulesMatch($groupRules, $presetRules)) {
                $found = $name;

                break;
            }
        }

        return $found;
    }

    /**
     * @param array<string, string[]> $actual
     * @param array<string, string[]> $expected
     *
     * @return bool
     */
    private static function rulesMatch(array $actual, array $expected): bool
    {
        $normalizeActions = static function (array $actions): array {
            $sorted = $actions;
            sort($sorted);

            return $sorted;
        };

        $filteredActual = [];
        foreach ($actual as $resource => $actions) {
            if (!str_contains($resource, ':')) {
                $filteredActual[$resource] = $normalizeActions($actions);
            }
        }

        $normalizedExpected = [];
        foreach ($expected as $resource => $actions) {
            $normalizedExpected[$resource] = $normalizeActions($actions);
        }

        ksort($filteredActual);
        ksort($normalizedExpected);

        return $filteredActual === $normalizedExpected;
    }

}
