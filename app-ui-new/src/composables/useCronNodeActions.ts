import { useToast } from './useToast'
import api from '@/services/api'
import { getNextCronRun } from '@/utils/cronParser'

export interface RunProcessResult {
  message: string
  started: boolean
  startingPoint: string
  correlationId: string
}

export function useCronNodeActions() {
  const { showToast } = useToast()

  const toggleNodeState = async (nodeId: string, currentState: boolean): Promise<boolean> => {
    const newState = !currentState
    try {
      await api.patch(`/api/nodes/${nodeId}`, { enabled: newState })
      showToast(`Node ${newState ? 'enabled' : 'disabled'} successfully`, 'success', 2000)
      return newState
    } catch (error) {
      showToast(`Failed to ${newState ? 'enable' : 'disable'} node`, 'error', 3000)
      throw error
    }
  }

  const runProcess = async (
    topologyId: string,
    nodeId: string,
    nodeName: string,
    jsonData: string,
  ): Promise<RunProcessResult[]> => {
    try {
      const response = await api.post<RunProcessResult[]>(
        `/api/topologies/${topologyId}/run`,
        { startingPoints: [nodeId], body: jsonData },
      )
      showToast(`Process "${nodeName}" started successfully`, 'success', 3000)
      return response.data ?? []
    } catch (error) {
      showToast('Failed to start process', 'error', 3000)
      throw error
    }
  }

  const updateCrontab = async (nodeId: string, crontab: string): Promise<string> => {
    try {
      await api.patch(`/api/nodes/${nodeId}`, {
        cron: {
          time: crontab,
          params: '',
        },
      })
      const nextRunDate = getNextCronRun(crontab)
      const nextRun = nextRunDate ? nextRunDate.toISOString() : ''
      showToast('Crontab configuration updated successfully', 'success', 2000)
      return nextRun
    } catch (error) {
      showToast('Failed to update crontab configuration', 'error', 3000)
      throw error
    }
  }

  return {
    toggleNodeState,
    runProcess,
    updateCrontab,
  }
}
