import { ref, type Ref } from 'vue'
import { fetchMyGroups, fetchPresets } from '@/services/groupsService'
import type { PresetDefinition } from '@/services/groupsService'
import type { AuthorizationProvider } from '@orchesty/ui-core'

const ROLE_HIERARCHY = [
  'chat_user',
  'monitoring',
  'process_management',
  'developer',
  'system_manager',
  'super_admin',
] as const

const loaded = ref(false)
const userRole = ref<string | null>(null)
const resolvedRules = ref<Record<string, string[]>>({})

function resolveRulesMap(preset: PresetDefinition): Record<string, string[]> {
  const map: Record<string, string[]> = {}
  for (const rule of preset.rules) {
    map[rule.resource] = rule.actions
  }
  return map
}

export function usePermissions(): {
  provider: AuthorizationProvider
  loaded: Ref<boolean>
  userRole: Ref<string | null>
  loadPermissions: () => Promise<void>
} {
  async function loadPermissions() {
    try {
      const [groupsResp, presets] = await Promise.all([
        fetchMyGroups(),
        fetchPresets(),
      ])

      const presetGroup = groupsResp.items.find((g) => g.preset != null)
      if (presetGroup?.preset) {
        userRole.value = presetGroup.preset
        const preset = presets.find((p) => p.name === presetGroup.preset)
        if (preset) {
          resolvedRules.value = resolveRulesMap(preset)
        }
      }
    } catch (error) {
      console.error('Failed to load user permissions:', error)
    } finally {
      loaded.value = true
    }
  }

  const provider: AuthorizationProvider = {
    can(permission: string): boolean {
      if (!loaded.value) return true
      const [resource, action] = permission.split(':')
      if (!resource || !action) return false
      return resolvedRules.value[resource]?.includes(action) ?? false
    },

    hasRole(role: string): boolean {
      if (!loaded.value) return false
      if (!userRole.value) return false
      const userIdx = ROLE_HIERARCHY.indexOf(userRole.value as typeof ROLE_HIERARCHY[number])
      const roleIdx = ROLE_HIERARCHY.indexOf(role as typeof ROLE_HIERARCHY[number])
      if (userIdx === -1 || roleIdx === -1) return false
      return userIdx >= roleIdx
    },
  }

  return { provider, loaded, userRole, loadPermissions }
}
