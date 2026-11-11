interface LastTopology {
  id: string
  name: string
  versionId?: string
  activeTab?: string
}

const LAST_TOPOLOGY_KEY = 'orchesty_last_topology'

export function useLastTopology() {
  const getLastTopology = (): LastTopology | null => {
    try {
      const stored = localStorage.getItem(LAST_TOPOLOGY_KEY)
      return stored ? JSON.parse(stored) : null
    } catch {
      return null
    }
  }

  const setLastTopology = (topology: LastTopology) => {
    try {
      localStorage.setItem(LAST_TOPOLOGY_KEY, JSON.stringify(topology))
    } catch (error) {
      console.error('Failed to save last topology:', error)
    }
  }

  const clearLastTopology = () => {
    try {
      localStorage.removeItem(LAST_TOPOLOGY_KEY)
    } catch (error) {
      console.error('Failed to clear last topology:', error)
    }
  }

  return {
    getLastTopology,
    setLastTopology,
    clearLastTopology,
  }
}

