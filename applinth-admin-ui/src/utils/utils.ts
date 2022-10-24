import { Locale } from "@/enums"
import {
  ApplicationDetail,
  ApplicationDetailRaw,
  IndexedApplicationDetail,
} from "@/types"
import store from "@/store"
import {
  ApplicationsMutations,
  applicationsNamespace,
} from "@/store/modules/applications"
import metadata from "./../../public/metadata.json"

export async function loadApplicationsDetails(): Promise<void> {
  store.commit(
    `${applicationsNamespace}/${ApplicationsMutations.FetchingApplicationsMetadata}`,
    true
  )

  const indexedMetadata: IndexedApplicationDetail = {}

  for (const item of metadata) {
    const app = transformApplicationDetail(
      item as unknown as ApplicationDetailRaw
    )
    if (app) indexedMetadata[item.name] = app
  }

  store.commit(
    `${applicationsNamespace}/${ApplicationsMutations.SetApplicationsMetadata}`,
    indexedMetadata
  )
  store.commit(
    `${applicationsNamespace}/${ApplicationsMutations.FetchingApplicationsMetadata}`,
    false
  )
}

export function transformApplicationDetail(
  application: ApplicationDetailRaw | undefined
): ApplicationDetail | null {
  if (!application) return null

  let description = null

  for (const descriptionData of application.description) {
    if (descriptionData.lang === Locale.En) {
      description = descriptionData.text
      break
    }
  }

  return {
    name: application.name,
    publicName: application.publicName,
    logo: application.logo,
    description,
    categories: application.categories,
  }
}
