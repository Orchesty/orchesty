import api from './api'
import { fetchApplications } from './applicationsService'

export type ActionOption = {
  name: string
  worker: string
  type: 'custom' | 'connector' | 'batch' | 'user'
  app?: string | null
  icon?: string
}

type NodeActionItem = {
  name: string
  app?: string | null
}

type ActionType = 'custom' | 'connector' | 'batch' | 'user'

const VALID_TYPES: ActionType[] = ['custom', 'connector', 'batch', 'user']

async function buildAppLogoMap(): Promise<Map<string, string>> {
  try {
    const workers = await fetchApplications()
    const map = new Map<string, string>()
    for (const worker of workers) {
      for (const app of worker.applications) {
        if (app.logo && app.key && !map.has(app.key)) {
          map.set(app.key, `<img src="${app.logo}" alt="" />`)
        }
      }
    }
    return map
  } catch {
    return new Map()
  }
}

export const topologyEditorService = {
  async getAllActions(): Promise<ActionOption[]> {
    const [response, logoMap] = await Promise.all([
      api.get<Record<string, Record<string, NodeActionItem[] | string[]>>>('/api/nodes/list/name'),
      buildAppLogoMap(),
    ])
    const data = response.data
    const actions: ActionOption[] = []

    for (const [sdkGroup, types] of Object.entries(data)) {
      for (const [type, items] of Object.entries(types)) {
        if (!VALID_TYPES.includes(type as ActionType)) continue
        if (!Array.isArray(items)) continue

        for (const item of items) {
          if (typeof item === 'string') {
            actions.push({
              name: item,
              worker: sdkGroup,
              type: type as ActionType,
            })
            continue
          }

          actions.push({
            name: item.name,
            worker: sdkGroup,
            type: type as ActionType,
            app: item.app ?? null,
            icon: item.app ? logoMap.get(item.app) : undefined,
          })
        }
      }
    }

    const unique = new Map<string, ActionOption>()
    for (const action of actions) {
      const key = `${action.worker}::${action.name}::${action.type}`
      if (!unique.has(key)) {
        unique.set(key, action)
      }
    }

    return Array.from(unique.values())
  },

  async getActionsByType(nodeType: ActionType): Promise<ActionOption[]> {
    const allActions = await this.getAllActions()
    return allActions.filter(action => action.type === nodeType)
  },

  async searchActions(query: string): Promise<ActionOption[]> {
    const allActions = await this.getAllActions()
    const lowerQuery = query.toLowerCase()
    return allActions.filter(action =>
      action.name.toLowerCase().includes(lowerQuery) ||
      action.worker.toLowerCase().includes(lowerQuery)
    )
  }
}
