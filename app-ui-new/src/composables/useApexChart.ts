import { ref, onMounted, onUnmounted } from 'vue'
import ApexCharts from 'apexcharts'

export interface UseApexChartOptions {
  darkModeSelector?: string
  onDarkModeChange?: (isDark: boolean) => void
}

export function useApexChart(options: UseApexChartOptions = {}) {
  const { darkModeSelector = 'dark', onDarkModeChange } = options
  
  const chartInstance = ref<ApexCharts | null>(null)
  const isDarkMode = ref(false)

  // Check if dark mode is active
  const checkDarkMode = () => {
    return document.documentElement.classList.contains(darkModeSelector)
  }

  // Update dark mode state
  const updateDarkMode = () => {
    const wasDark = isDarkMode.value
    isDarkMode.value = checkDarkMode()
    
    if (wasDark !== isDarkMode.value && onDarkModeChange) {
      onDarkModeChange(isDarkMode.value)
    }
  }

  // Initialize chart (synchronous like original HTML implementation)
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const initChart = (element: HTMLElement, chartOptions: any): ApexCharts | null => {
    try {
      if (chartInstance.value) {
        chartInstance.value.destroy()
        chartInstance.value = null
      }
      
      chartInstance.value = new ApexCharts(element, chartOptions)
      chartInstance.value.render()
      
      // Apply dark mode background if needed (like original implementation)
      setTimeout(() => {
        if (isDarkMode.value) {
          const svg = element.querySelector('svg')
          if (svg) {
            svg.style.backgroundColor = '#1f2937'
          }
        }
      }, 50)
      
      return chartInstance.value
    } catch (error) {
      console.error('Error initializing ApexChart:', error)
      return null
    }
  }

  // Update chart options
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const updateChart = (newOptions: any, redraw = false) => {
    if (chartInstance.value) {
      chartInstance.value.updateOptions(newOptions, redraw)
    }
  }

  // Update chart series
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const updateSeries = (newSeries: any[]) => {
    if (chartInstance.value) {
      chartInstance.value.updateSeries(newSeries)
    }
  }

  // Destroy chart
  const destroyChart = () => {
    if (chartInstance.value) {
      try {
        chartInstance.value.destroy()
      } catch (error) {
        // Ignore errors during chart destruction (e.g., if element already removed)
        console.debug('Chart destroy error (safe to ignore):', error)
      } finally {
        chartInstance.value = null
      }
    }
  }

  // Handle resize - REMOVED to prevent infinite loop
  // ApexCharts handles resize automatically, no manual intervention needed
  
  // Setup resize observer - DISABLED to prevent infinite loop
  // The resize observer was causing infinite loop by dispatching resize events
  // ApexCharts chart.redrawOnWindowResize handles this automatically
  let resizeObserver: ResizeObserver | null = null
  
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const setupResizeObserver = (_element: HTMLElement) => {
    // DISABLED: ResizeObserver caused infinite loop
    // ApexCharts handles resize via window resize events automatically
    return
  }

  // Setup mutation observer for dark mode changes
  let mutationObserver: MutationObserver | null = null
  
  const setupDarkModeObserver = () => {
    if (typeof MutationObserver !== 'undefined' && !mutationObserver) {
      mutationObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            updateDarkMode()
          }
        })
      })
      
      mutationObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
      })
    }
  }

  // Handle rerender-charts event (like original Flowbite template)
  const handleRerenderCharts = () => {
    console.log('📊 Received rerender-charts event')
    updateDarkMode()
  }

  onMounted(() => {
    updateDarkMode()
    setupDarkModeObserver()
    // Listen for rerender-charts event (like original Flowbite template)
    document.addEventListener('rerender-charts', handleRerenderCharts)
    // REMOVED: window.addEventListener('resize', handleResize) - causes infinite loop
    // ApexCharts handles resize automatically via chart.redrawOnWindowResize
  })

  onUnmounted(() => {
    destroyChart()
    
    if (resizeObserver) {
      resizeObserver.disconnect()
      resizeObserver = null
    }
    
    if (mutationObserver) {
      mutationObserver.disconnect()
      mutationObserver = null
    }
    
    document.removeEventListener('rerender-charts', handleRerenderCharts)
    // REMOVED: window.removeEventListener('resize', handleResize)
  })

  return {
    chartInstance,
    isDarkMode,
    initChart,
    updateChart,
    updateSeries,
    destroyChart,
    setupResizeObserver,
  }
}

// Helper functions for common chart configurations

export function getChartColors(isDark: boolean) {
  return {
    background: isDark ? '#1f2937' : '#ffffff',
    text: isDark ? '#9CA3AF' : '#6B7280',
    border: isDark ? '#374151' : '#F3F4F6',
    primary: isDark ? '#3B82F6' : '#2563EB',
    success: isDark ? '#22C55E' : '#16A34A',
    danger: isDark ? '#EF4444' : '#DC2626',
    transparent: isDark ? 'rgba(31, 41, 55, 0)' : 'rgba(255, 255, 255, 0)',
  }
}

export function getBaseChartOptions(isDark: boolean) {
  const colors = getChartColors(isDark)
  
  return {
    chart: {
      fontFamily: 'Inter, sans-serif',
      background: colors.background,
      foreColor: colors.text,
      toolbar: {
        show: false,
      },
    },
    grid: {
      borderColor: colors.border,
    },
    tooltip: {
      theme: isDark ? 'dark' : 'light',
    },
  }
}

