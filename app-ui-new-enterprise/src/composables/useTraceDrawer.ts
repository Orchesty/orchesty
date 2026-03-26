import { ref } from 'vue'

const isTraceDrawerOpen = ref(false)

export function useTraceDrawer() {
  const toggleDrawer = () => {
    isTraceDrawerOpen.value = !isTraceDrawerOpen.value
  }
  
  const openDrawer = () => {
    isTraceDrawerOpen.value = true
  }
  
  const closeDrawer = () => {
    isTraceDrawerOpen.value = false
  }
  
  return {
    isTraceDrawerOpen,
    toggleDrawer,
    openDrawer,
    closeDrawer
  }
}

