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

    /**
     * @var array<string, array{label: string, description: string, level: int}>
     */
    private const array PRESET_META = [
        self::CHAT_USER => [
            'label'       => 'Chat User',
            'description' => 'Access to Trace chat only. Topology run permissions are assigned via access groups.',
            'level'       => 5,
        ],
        self::MONITORING => [
            'label'       => 'Monitoring',
            'description' => 'Read-only access to dashboard, topologies, scheduled tasks, processes, logs and failed messages.',
            'level'       => 4,
        ],
        self::PROCESS_MANAGEMENT => [
            'label'       => 'Process Management',
            'description' => 'Monitoring + manage scheduled tasks, run/enable topologies, reprocess failed messages.',
            'level'       => 3,
        ],
        self::DEVELOPER => [
            'label'       => 'Developer',
            'description' => 'Process Management + full topology editing, application management.',
            'level'       => 2,
        ],
        self::SYSTEM_MANAGER => [
            'label'       => 'System Manager',
            'description' => 'Developer + settings, SDKs, API tokens.',
            'level'       => 1,
        ],
        self::SUPER_ADMIN => [
            'label'       => 'Super Admin',
            'description' => 'Full access including user and group management.',
            'level'       => 0,
        ],
    ];

    /**
     * Rules added at each level (delta from the previous level).
     *
     * @var array<string, array<string, string[]>>
     */
    private const array PRESET_RULES = [
        self::CHAT_USER => [
            ResourceEnum::TRACE => [ActionEnum::READ],
        ],
        self::MONITORING => [
            ResourceEnum::OVERVIEW       => [ActionEnum::READ],
            ResourceEnum::TOPOLOGY       => [ActionEnum::READ],
            ResourceEnum::SCHEDULED_TASK => [ActionEnum::READ],
            ResourceEnum::PROCESS        => [ActionEnum::READ],
            ResourceEnum::LOGS           => [ActionEnum::READ],
            ResourceEnum::CONNECTOR      => [ActionEnum::READ],
            ResourceEnum::LIMITER        => [ActionEnum::READ],
            ResourceEnum::USER_TASK      => [ActionEnum::READ],
        ],
        self::PROCESS_MANAGEMENT => [
            ResourceEnum::SCHEDULED_TASK => [ActionEnum::READ, ActionEnum::WRITE],
            ResourceEnum::TOPOLOGY       => [ActionEnum::READ, ActionEnum::RUN],
            ResourceEnum::USER_TASK      => [ActionEnum::READ, ActionEnum::WRITE],
        ],
        self::DEVELOPER => [
            ResourceEnum::TOPOLOGY   => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE, ActionEnum::RUN],
            ResourceEnum::APPLICATION => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE],
        ],
        self::SYSTEM_MANAGER => [
            ResourceEnum::SETTINGS => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE],
        ],
        self::SUPER_ADMIN => [
            ResourceEnum::USER => [ActionEnum::READ, ActionEnum::WRITE, ActionEnum::DELETE],
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
                'name'        => $name,
                'label'       => $meta['label'],
                'description' => $meta['description'],
                'level'       => $meta['level'],
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
