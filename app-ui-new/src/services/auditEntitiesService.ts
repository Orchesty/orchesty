import type { AuditEntity } from '@/types/settings'
import auditEntitiesDataJson from '@/assets/mock-data/audit-entities-data.json'

// Mock data
let entitiesData = [...auditEntitiesDataJson.data] as AuditEntity[]

/**
 * Fetch all audit entities (no pagination)
 */
export async function fetchAuditEntities(): Promise<AuditEntity[]> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  return [...entitiesData]
}

/**
 * Create a new audit entity
 */
export async function createEntity(
  data: Omit<AuditEntity, 'id'>
): Promise<AuditEntity> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  const newEntity: AuditEntity = {
    id: `entity-${Date.now()}`,
    ...data,
  }

  entitiesData.push(newEntity)

  return newEntity
}

/**
 * Update an existing audit entity
 */
export async function updateEntity(
  id: string,
  data: Partial<AuditEntity>
): Promise<AuditEntity> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  const index = entitiesData.findIndex((e) => e.id === id)
  if (index === -1) {
    throw new Error(`Entity with id ${id} not found`)
  }

  entitiesData[index] = {
    ...entitiesData[index],
    ...data,
  }

  return entitiesData[index]
}

/**
 * Delete an audit entity
 */
export async function deleteEntity(id: string): Promise<void> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 400))

  const index = entitiesData.findIndex((e) => e.id === id)
  if (index === -1) {
    throw new Error(`Entity with id ${id} not found`)
  }

  entitiesData.splice(index, 1)
}

