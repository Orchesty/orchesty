import api from './api'

export type ActionOption = {
  name: string
  worker: string
  type: 'custom' | 'connector' | 'batch'
  app?: string | null
}

type NodeActionItem = {
  name: string
  app?: string | null
}

type ActionType = 'custom' | 'connector' | 'batch'

const VALID_TYPES: ActionType[] = ['custom', 'connector', 'batch']

export const topologyEditorService = {
  async getAllActions(): Promise<ActionOption[]> {
    const response = await api.get<Record<string, Record<string, NodeActionItem[] | string[]>>>('/api/nodes/list/name')
    const data = response.data
    const actions: ActionOption[] = []

    for (const [sdkGroup, types] of Object.entries(data)) {
      for (const [type, items] of Object.entries(types)) {
        if (!VALID_TYPES.includes(type as ActionType)) continue
        if (!Array.isArray(items)) continue

        for (const item of items) {
          if (typeof item === 'string') continue

          actions.push({
            name: item.name,
            worker: sdkGroup,
            type: type as ActionType,
            app: item.app ?? null,
          })
        }
      }
    }

    return actions
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
