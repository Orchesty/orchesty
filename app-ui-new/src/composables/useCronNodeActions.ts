import { useToast } from './useToast'

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
          showToast({
            type: 'error',
            message: `Failed to ${currentState ? 'disable' : 'enable'} node`,
            duration: 3000
          })
          reject(error)
          return
        }

        // Success
        const newState = !currentState
        showToast({
          type: 'success',
          message: `Node ${newState ? 'enabled' : 'disabled'} successfully`,
          duration: 2000
        })
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
          showToast({
            type: 'error',
            message: 'Failed to start process',
            duration: 3000
          })
          reject(error)
          return
        }

        // Success
        showToast({
          type: 'success',
          message: `Process "${nodeName}" started successfully`,
          duration: 3000
        })
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
          showToast({
            type: 'error',
            message: 'Failed to update crontab configuration',
            duration: 3000
          })
          reject(error)
          return
        }

        // Calculate next run time (simplified - just add 15 minutes)
        const nextRun = new Date(Date.now() + 15 * 60 * 1000).toISOString()

        // Success
        showToast({
          type: 'success',
          message: 'Crontab configuration updated successfully',
          duration: 2000
        })
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

