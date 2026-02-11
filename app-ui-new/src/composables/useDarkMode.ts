import { onMounted } from 'vue'

export function useDarkMode() {
  const initDarkMode = () => {
    const toggle = document.getElementById('theme-toggle') as HTMLInputElement | null
    if (!toggle) {
      console.error('Theme toggle element not found!')
      return
    }

    // Load saved preference or use system preference
    if (
      localStorage.theme === 'dark' ||
      (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
      document.documentElement.classList.add('dark')
      toggle.checked = true
    } else {
      document.documentElement.classList.remove('dark')
      toggle.checked = false
    }

    // Handle toggle change
    const handleChange = function (this: HTMLInputElement) {
      if (this.checked) {
        document.documentElement.classList.add('dark')
        localStorage.theme = 'dark'
      } else {
        document.documentElement.classList.remove('dark')
        localStorage.theme = 'light'
      }
      
      // Dispatch custom event to re-render charts (like original Flowbite template)
      console.log('🌓 Dark mode toggled, dispatching rerender-charts event')
      document.dispatchEvent(new Event('rerender-charts'))
    }

    toggle.addEventListener('change', handleChange)

    // Cleanup function
    return () => {
      toggle.removeEventListener('change', handleChange)
    }
  }

  onMounted(() => {
    initDarkMode()
  })

  return {
    initDarkMode,
  }
}

