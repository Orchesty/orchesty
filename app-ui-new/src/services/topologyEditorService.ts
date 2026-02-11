import { getAllActions, getAvailableActions, type ActionOption } from '@/assets/mock-data/actions'

/**
 * Service for providing actions to the topology editor
 * This simulates backend API calls for actions
 */
export const topologyEditorService = {
  /**
   * Get all available actions
   */
  async getAllActions(): Promise<ActionOption[]> {
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 100))
    return getAllActions()
  },

  /**
   * Get actions filtered by node type
   */
  async getActionsByType(nodeType: 'custom' | 'connector' | 'batch'): Promise<ActionOption[]> {
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 100))
    return getAvailableActions(nodeType)
  },

  /**
   * Search actions by name
   */
  async searchActions(query: string): Promise<ActionOption[]> {
    const allActions = await this.getAllActions()
    const lowerQuery = query.toLowerCase()
    return allActions.filter(action => 
      action.name.toLowerCase().includes(lowerQuery) ||
      action.worker.toLowerCase().includes(lowerQuery)
    )
  }
}



