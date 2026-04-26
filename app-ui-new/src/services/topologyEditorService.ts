import api from './api'
import { fetchApplications } from './applicationsService'

export type ActionOption = {
  name: string
  worker: string
  type: 'custom' | 'connector' | 'batch' | 'user' | 'webhook'
  app?: string | null
  icon?: string
  /** Webhook only: bare event name (`order.created`) without the app prefix. */
  event?: string
  /** Optional human-readable description shown in the picker submenu. */
  description?: string
  /** Webhook only: default subscription parameters carried into the action. */
  parameters?: Record<string, unknown>
}

type NodeActionItem = {
  name: string
  app?: string | null
}

type WebhookCatalogEvent = {
  name: string
  parameters?: Record<string, unknown>
  description?: string
}

type WebhookCatalogApp = {
  application: string
  name?: string
  logo?: string
  events?: WebhookCatalogEvent[]
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
      api.get<Record<string, Record<string, NodeActionItem[] | string[] | WebhookCatalogApp[]>>>('/api/nodes/list/name'),
      buildAppLogoMap(),
    ])
    const data = response.data
    const actions: ActionOption[] = []

    for (const [sdkGroup, types] of Object.entries(data)) {
      for (const [type, items] of Object.entries(types)) {
        if (!Array.isArray(items)) continue

        // Webhook bucket has a different shape: [{ application, events: [...] }, ...]
        // Each event becomes a single action whose canonical name is `app.event`.
        // `worker` MUST stay as the SDK service id (e.g. `node-sdk`) — the
        // backend uses it to route subscribe / unsubscribe calls. The
        // human-readable label belongs in `description`, not `worker`.
        if (type === 'webhook') {
          for (const entry of items as WebhookCatalogApp[]) {
            const app = entry?.application
            if (!app || !Array.isArray(entry.events)) continue
            const icon = logoMap.get(app)
            for (const ev of entry.events) {
              if (!ev?.name) continue
              actions.push({
                name: `${app}.${ev.name}`,
                worker: sdkGroup,
                type: 'webhook',
                app,
                event: ev.name,
                description: ev.description?.trim() || `${entry.name ?? app} · ${ev.name}`,
                parameters: ev.parameters,
                icon,
              })
            }
          }
          continue
        }

        if (!VALID_TYPES.includes(type as ActionType)) continue

        for (const item of items as NodeActionItem[] | string[]) {
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

  async getActionsByType(nodeType: ActionType | 'webhook'): Promise<ActionOption[]> {
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
