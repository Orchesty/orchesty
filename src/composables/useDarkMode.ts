import { ref, onMounted } from 'vue'

const isDark = ref(false)

export function useDarkMode() {
  const initDarkMode = () => {
    isDark.value =
      localStorage.theme === 'dark' ||
      (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)

    if (isDark.value) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  const toggleDarkMode = () => {
    isDark.value = !isDark.value
    if (isDark.value) {
      document.documentElement.classList.add('dark')
      localStorage.theme = 'dark'
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.theme = 'light'
    }
    document.dispatchEvent(new Event('rerender-charts'))
  }

  onMounted(() => {
    initDarkMode()
  })

  return {
    isDark,
    toggleDarkMode,
    initDarkMode,
  }
}
