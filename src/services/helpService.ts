import api from './api'

export interface HelpManifestEntry {
  slug: string
  title: string
  helpId: string
  order: number
  parent: string | null
}

export interface HelpPage {
  title: string
  helpId: string
  content: string
}

let manifestCache: HelpManifestEntry[] | null = null
let searchIndexCache: unknown | null = null

export async function fetchHelpManifest(): Promise<HelpManifestEntry[]> {
  if (manifestCache) return manifestCache
  const response = await api.get<HelpManifestEntry[]>('/api/help/manifest')
  manifestCache = response.data
  return response.data
}

export async function fetchHelpPage(helpId: string): Promise<HelpPage> {
  const response = await api.get<HelpPage>(`/api/help/page/${helpId}`)
  return response.data
}

export async function fetchHelpSearchIndex(): Promise<unknown> {
  if (searchIndexCache) return searchIndexCache
  const response = await api.get('/api/help/search-index')
  searchIndexCache = response.data
  return response.data
}
