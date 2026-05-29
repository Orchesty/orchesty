<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { marked } from 'marked'
import MiniSearch from 'minisearch'
import { CircleHelp, Search, ChevronRight, ChevronDown, ArrowLeft, X } from 'lucide-vue-next'
import { fetchHelpManifest, fetchHelpPage, fetchHelpSearchIndex } from '@/services/helpService'
import type { HelpManifestEntry, HelpPage } from '@/services/helpService'

interface Props {
  modelValue: boolean
  helpId?: string
}

const props = withDefaults(defineProps<Props>(), {
  helpId: undefined,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const drawerInstance = ref<any>(null)
const manifest = ref<HelpManifestEntry[]>([])
const currentPage = ref<HelpPage | null>(null)
const loading = ref(false)
const helpAvailable = ref<boolean | null>(null)
const searchQuery = ref('')
const searchResults = ref<{ slug: string; title: string; helpId: string }[]>([])
let miniSearch: MiniSearch | null = null
let searchIndexFailed = false

const renderedHtml = computed(() => {
  if (!currentPage.value?.content) return ''
  return marked(currentPage.value.content) as string
})

const expandedSections = ref<Set<string>>(new Set())

function toggleSection(key: string) {
  if (expandedSections.value.has(key)) {
    expandedSections.value.delete(key)
  } else {
    expandedSections.value.add(key)
  }
}

const navTree = computed(() => {
  const grouped = new Map<string, { label: string; items: HelpManifestEntry[] }>()
  const rootItems: HelpManifestEntry[] = []

  for (const entry of manifest.value) {
    if (entry.parent) {
      if (!grouped.has(entry.parent)) {
        const label = entry.parent
          .split('/')
          .pop()!
          .replace(/[-_]/g, ' ')
          .replace(/\b\w/g, (c) => c.toUpperCase())
        grouped.set(entry.parent, { label, items: [] })
      }
      grouped.get(entry.parent)!.items.push(entry)
    } else {
      rootItems.push(entry)
    }
  }

  const singleItems: HelpManifestEntry[] = []
  const multiSections = new Map<string, { label: string; items: HelpManifestEntry[] }>()

  for (const [key, section] of grouped) {
    if (section.items.length === 1) {
      singleItems.push(section.items[0]!)
    } else {
      multiSections.set(key, section)
    }
  }

  return { rootItems, singleItems, multiSections }
})

const breadcrumbs = computed(() => {
  if (!currentPage.value) return []
  const entry = manifest.value.find((e) => e.helpId === currentPage.value?.helpId)
  if (!entry) return []

  const crumbs: { label: string; helpId?: string }[] = []
  if (entry.parent) {
    const sectionLabel = entry.parent
      .split('/')
      .pop()!
      .replace(/[-_]/g, ' ')
      .replace(/\b\w/g, (c) => c.toUpperCase())
    crumbs.push({ label: sectionLabel })
  }
  crumbs.push({ label: entry.title })
  return crumbs
})

const isSearching = computed(() => searchQuery.value.trim().length > 0)

async function loadManifest() {
  if (helpAvailable.value === false) return
  try {
    manifest.value = await fetchHelpManifest()
    helpAvailable.value = true
  } catch {
    helpAvailable.value = false
  }
}

async function loadPage(helpId: string) {
  if (helpAvailable.value === false) return
  loading.value = true
  try {
    currentPage.value = await fetchHelpPage(helpId)
  } catch {
    currentPage.value = null
  } finally {
    loading.value = false
  }
}

async function ensureSearchIndex() {
  if (miniSearch || searchIndexFailed || helpAvailable.value === false) return
  try {
    const raw = await fetchHelpSearchIndex()
    miniSearch = MiniSearch.loadJSON(JSON.stringify(raw), {
      fields: ['title', 'text'],
      storeFields: ['title', 'slug', 'helpId'],
    })
  } catch {
    searchIndexFailed = true
  }
}

function handleSearch() {
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  ensureSearchIndex().then(() => {
    if (!miniSearch) return
    searchResults.value = miniSearch.search(searchQuery.value.trim()) as unknown as {
      slug: string
      title: string
      helpId: string
    }[]
  })
}

function handleNavigate(helpId: string) {
  searchQuery.value = ''
  searchResults.value = []
  loadPage(helpId)
}

function handleBack() {
  currentPage.value = null
}

function handleClose() {
  emit('update:modelValue', false)
}

watch(
  () => searchQuery.value,
  () => handleSearch(),
)

watch(
  () => props.modelValue,
  async (open) => {
    await nextTick()
    if (drawerInstance.value) {
      if (open) {
        drawerInstance.value.show()
        if (manifest.value.length === 0) {
          await loadManifest()
        }
        if (props.helpId) {
          await loadPage(props.helpId)
        }
      } else {
        drawerInstance.value.hide()
      }
    }
  },
)

watch(
  () => props.helpId,
  async (newId) => {
    if (newId && props.modelValue) {
      await loadPage(newId)
    }
  },
)

onMounted(async () => {
  await nextTick()
  const el = document.getElementById('help-drawer')
  if (el) {
    const { Drawer } = await import('flowbite')
    drawerInstance.value = new Drawer(el, {
      placement: 'right',
      backdrop: false,
      bodyScrolling: true,
      edge: false,
      edgeOffset: '',
      onHide: () => {
        emit('update:modelValue', false)
      },
    })
  }
})

onBeforeUnmount(() => {
  if (drawerInstance.value) {
    drawerInstance.value.hide()
  }
})
</script>

<template>
  <div
    id="help-drawer"
    class="fixed top-[53px] right-0 z-40 h-[calc(100vh-53px)] w-[420px] translate-x-full border-l border-gray-200 bg-white transition-transform dark:border-gray-700 dark:bg-gray-800"
    tabindex="-1"
    aria-labelledby="help-drawer-label"
    aria-hidden="true"
  >
    <div class="flex h-full flex-col">
      <!-- Header -->
      <div class="relative shrink-0 border-b border-gray-200 p-4 dark:border-gray-700">
        <div class="flex items-center gap-2">
          <CircleHelp class="h-5 w-5 text-gray-500 dark:text-gray-400" />
          <h5
            id="help-drawer-label"
            class="text-sm font-semibold uppercase text-gray-500 dark:text-gray-400"
          >
            Help
          </h5>
        </div>
        <button
          type="button"
          class="absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          @click="handleClose"
        >
          <X class="h-5 w-5" />
          <span class="sr-only">Close help</span>
        </button>
      </div>

      <!-- Search -->
      <div class="shrink-0 border-b border-gray-200 p-3 dark:border-gray-700">
        <div class="relative">
          <Search
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search help..."
            class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
            @focus="ensureSearchIndex"
          />
        </div>
      </div>

      <!-- Content area -->
      <div class="min-h-0 flex-1 overflow-y-auto">
        <!-- Search results -->
        <div v-if="isSearching" class="p-4">
          <p class="mb-3 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
            Search results
          </p>
          <ul v-if="searchResults.length > 0" class="space-y-1">
            <li v-for="result in searchResults" :key="result.slug">
              <button
                class="w-full rounded-lg px-3 py-2 text-left text-sm text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                @click="handleNavigate(result.helpId)"
              >
                {{ result.title }}
              </button>
            </li>
          </ul>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">
            No results found.
          </p>
        </div>

        <!-- Page view -->
        <div v-else-if="currentPage" class="p-4">
          <!-- Back + Breadcrumbs -->
          <div class="mb-4 flex items-center gap-2">
            <button
              class="inline-flex items-center rounded-lg p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              @click="handleBack"
            >
              <ArrowLeft class="h-4 w-4" />
            </button>
            <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
              <button
                class="hover:text-gray-900 hover:underline dark:hover:text-white"
                @click="handleBack"
              >
                Help
              </button>
              <template v-for="(crumb, i) in breadcrumbs" :key="i">
                <ChevronRight class="h-3 w-3" />
                <span :class="i === breadcrumbs.length - 1 ? 'text-gray-900 dark:text-white' : ''">
                  {{ crumb.label }}
                </span>
              </template>
            </nav>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="flex items-center justify-center py-8">
            <svg
              class="h-6 w-6 animate-spin text-primary-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
          </div>

          <!-- Rendered markdown -->
          <div
            v-else
            class="prose prose-sm max-w-none dark:prose-invert prose-headings:scroll-mt-4 prose-a:text-primary-600 dark:prose-a:text-primary-400"
            v-html="renderedHtml"
          />
        </div>

        <!-- Navigation (home) -->
        <div v-else class="p-4">
          <ul class="space-y-1">
            <!-- Root items -->
            <li v-for="item in navTree.rootItems" :key="item.slug">
              <button
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                @click="handleNavigate(item.helpId)"
              >
                {{ item.title }}
                <ChevronRight class="h-4 w-4 text-gray-400" />
              </button>
            </li>

            <!-- Single-page sections (no section header) -->
            <li v-for="item in navTree.singleItems" :key="item.slug">
              <button
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                @click="handleNavigate(item.helpId)"
              >
                {{ item.title }}
                <ChevronRight class="h-4 w-4 text-gray-400" />
              </button>
            </li>

            <!-- Multi-page sections (collapsible) -->
            <li v-for="[key, section] in navTree.multiSections" :key="key">
              <button
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                @click="toggleSection(key)"
              >
                {{ section.label }}
                <component
                  :is="expandedSections.has(key) ? ChevronDown : ChevronRight"
                  class="h-4 w-4 text-gray-400"
                />
              </button>
              <ul v-if="expandedSections.has(key)" class="mt-1 space-y-1 pl-3">
                <li v-for="item in section.items" :key="item.slug">
                  <button
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    @click="handleNavigate(item.helpId)"
                  >
                    {{ item.title }}
                    <ChevronRight class="h-4 w-4 text-gray-400" />
                  </button>
                </li>
              </ul>
            </li>
          </ul>

          <!-- Empty state -->
          <div
            v-if="manifest.length === 0 && !loading"
            class="flex flex-col items-center py-8 text-center"
          >
            <CircleHelp class="mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
            <p class="text-sm text-gray-500 dark:text-gray-400">
              No help articles available.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
