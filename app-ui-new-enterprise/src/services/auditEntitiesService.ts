import api from './api'
import type {
  AuditEntity,
  AuditEntityApiResponse,
  AuditEntitiesListResponse,
} from '@/types/settings'

// Generate key from name (slugify to kebab-case)
function generateKey(name: string): string {
  return name
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

// Map API entity to component format
function mapApiEntityToEntity(apiEntity: AuditEntityApiResponse): AuditEntity {
  return {
    id: apiEntity.id,
    name: apiEntity.name,
    attributes: apiEntity.fields.map(field => ({
      name: field.key,
      description: field.name,
    })),
  }
}

// Map component entity to API format
function mapEntityToApiEntity(entity: Omit<AuditEntity, 'id'> | Partial<AuditEntity>, entityName?: string) {
  const name = entity.name || entityName || ''
  return {
    key: generateKey(name),
    name: name,
    fields: (entity.attributes || []).map(attr => ({
      key: attr.name,
      name: attr.description,
    })),
  }
}

/**
 * Fetch all audit entities (no pagination)
 */
export async function fetchAuditEntities(): Promise<AuditEntity[]> {
  const response = await api.get<AuditEntitiesListResponse>('/api/audit/entities')

  return response.data.items.map(mapApiEntityToEntity)
}

/**
 * Create a new audit entity
 */
export async function createEntity(
  data: Omit<AuditEntity, 'id'>
): Promise<AuditEntity> {
  const requestData = mapEntityToApiEntity(data)

  const response = await api.post<AuditEntityApiResponse>('/api/audit/entities', requestData)

  return mapApiEntityToEntity(response.data)
}

/**
 * Update an existing audit entity
 */
export async function updateEntity(
  id: string,
  data: Partial<AuditEntity>
): Promise<AuditEntity> {
  const requestData = mapEntityToApiEntity(data)

  const response = await api.put<AuditEntityApiResponse>(`/api/audit/entities/${id}`, requestData)

  return mapApiEntityToEntity(response.data)
}

/**
 * Delete an audit entity
 */
export async function deleteEntity(id: string): Promise<void> {
  await api.delete(`/api/audit/entities/${id}`)
}
