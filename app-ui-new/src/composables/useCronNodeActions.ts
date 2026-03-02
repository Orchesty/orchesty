import { useToast } from './useToast'
import { getNextCronRun } from '@/utils/cronParser'

export function useCronNodeActions() {
  const { showToast } = useToast()

  /**
   * Toggle node enabled/disabled state
   */
  const toggleNodeState = async (nodeId: string, currentState: boolean): Promise<boolean> => {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        // Simulate random error (10% chance)
        if (Math.random() < 0.1) {
          const error = new Error('Failed to toggle node state')
          showToast(`Failed to ${currentState ? 'disable' : 'enable'} node`, 'error', 3000)
          reject(error)
          return
        }

        // Success
        const newState = !currentState
        showToast(`Node ${newState ? 'enabled' : 'disabled'} successfully`, 'success', 2000)
        resolve(newState)
      }, 500)
    })
  }

  /**
   * Run process with JSON data
   */
  const runProcess = async (nodeId: string, nodeName: string, jsonData: string): Promise<void> => {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        // Simulate random error (15% chance)
        if (Math.random() < 0.15) {
          const error = new Error('Failed to start process')
          showToast('Failed to start process', 'error', 3000)
          reject(error)
          return
        }

        // Success
        showToast(`Process "${nodeName}" started successfully`, 'success', 3000)
        resolve()
      }, 800)
    })
  }

  /**
   * Update crontab configuration
   */
  const updateCrontab = async (nodeId: string, crontab: string): Promise<string> => {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        // Simulate random error (10% chance)
        if (Math.random() < 0.1) {
          const error = new Error('Failed to update crontab')
          showToast('Failed to update crontab configuration', 'error', 3000)
          reject(error)
          return
        }

        const nextRunDate = getNextCronRun(crontab)
        const nextRun = nextRunDate ? nextRunDate.toISOString() : new Date().toISOString()

        // Success
        showToast('Crontab configuration updated successfully', 'success', 2000)
        resolve(nextRun)
      }, 600)
    })
  }

  return {
    toggleNodeState,
    runProcess,
    updateCrontab
  }
}



