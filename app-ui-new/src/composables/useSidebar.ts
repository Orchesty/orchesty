import { onMounted, onUnmounted } from 'vue'

export function useSidebar() {
  let isExpanded = false

  const collapseSidebar = () => {
    const sidebar = document.getElementById('orchesty-sidebar')
    if (!sidebar) return

    // Hide text elements
    document.querySelectorAll('[data-sidebar-collapse-hide]').forEach((el) => {
      el.classList.add('hidden')
    })

    // Show collapse-only elements (like badges)
    document.querySelectorAll('[data-sidebar-collapse-show]').forEach((el) => {
      el.classList.remove('hidden')
    })

    // Set sidebar width
    sidebar.classList.remove('w-64')
    sidebar.classList.add('w-16')
  }

  const expandSidebar = () => {
    const sidebar = document.getElementById('orchesty-sidebar')
    if (!sidebar) return

    // Show text elements
    document.querySelectorAll('[data-sidebar-collapse-hide]').forEach((el) => {
      el.classList.remove('hidden')
    })

    // Hide collapse-only elements (like badges)
    document.querySelectorAll('[data-sidebar-collapse-show]').forEach((el) => {
      el.classList.add('hidden')
    })

    // Set sidebar width
    sidebar.classList.remove('w-16')
    sidebar.classList.add('w-64')
  }

  const handleMouseEnter = () => {
    expandSidebar()
    isExpanded = true
  }

  const handleMouseLeave = () => {
    collapseSidebar()
    isExpanded = false
  }

  onMounted(() => {
    const sidebar = document.getElementById('orchesty-sidebar')
    if (!sidebar) {
      console.error('Orchesty sidebar: Sidebar element not found!')
      return
    }

    // Initialize as collapsed
    collapseSidebar()

    // Add hover listeners
    sidebar.addEventListener('mouseenter', handleMouseEnter)
    sidebar.addEventListener('mouseleave', handleMouseLeave)
  })

  onUnmounted(() => {
    const sidebar = document.getElementById('orchesty-sidebar')
    if (sidebar) {
      sidebar.removeEventListener('mouseenter', handleMouseEnter)
      sidebar.removeEventListener('mouseleave', handleMouseLeave)
    }
  })

  return {
    collapseSidebar,
    expandSidebar,
  }
}

