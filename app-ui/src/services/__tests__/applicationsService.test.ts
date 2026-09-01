import { describe, it, expect } from 'vitest'
import { normalizeChoices, mapApiInstallToComponent, groupKeyOf } from '../applicationsService'
import type { ApplicationInstallApiResponse, ApplicationSetting } from '@/types/applications'

describe('normalizeChoices', () => {
  it('returns an empty array for empty/undefined input', () => {
    expect(normalizeChoices([])).toEqual([])
    expect(normalizeChoices(undefined as never)).toEqual([])
  })

  it('maps primitive id choices to value == label', () => {
    expect(normalizeChoices(['a', 'b'])).toEqual([
      { value: 'a', label: 'a' },
      { value: 'b', label: 'b' },
    ])
  })

  it('maps `{ "<id>": "<name>" }` choices to id saved, name shown', () => {
    expect(
      normalizeChoices([
        { '4663d4c7-40ba-4146-820b-672ca25ce6f6': 'restarthubnuti.cz' },
        { 'id-2': 'eshop-two.cz' },
      ]),
    ).toEqual([
      { value: '4663d4c7-40ba-4146-820b-672ca25ce6f6', label: 'restarthubnuti.cz' },
      { value: 'id-2', label: 'eshop-two.cz' },
    ])
  })
})

describe('mapApiInstallToComponent', () => {
  const baseField = {
    key: 'eshopId',
    label: 'Eshop',
    description: '',
    required: true,
    readOnly: false,
    disabled: false,
    choices: [] as never,
  }

  const buildResponse = (field: Record<string, unknown>): ApplicationInstallApiResponse =>
    ({
      name: 'Demo',
      authorization_type: 'basic',
      application_type: 'cron',
      key: 'demo',
      description: '',
      info: '',
      logo: '',
      isInstallable: true,
      host: '',
      applicationSettings: {
        form: {
          key: 'form',
          publicName: 'Form',
          description: '',
          readOnly: false,
          fields: [field],
        },
      },
    }) as unknown as ApplicationInstallApiResponse

  it('keeps a multiselect value as an array (not a comma-joined string)', () => {
    const install = mapApiInstallToComponent(
      buildResponse({ ...baseField, type: 'multiselect', value: ['id-1', 'id-3'] }),
      'node-sdk',
    )
    expect(install.applicationSettings[0]?.value).toEqual(['id-1', 'id-3'])
  })

  it('defaults an empty multiselect value to an empty array', () => {
    const install = mapApiInstallToComponent(
      buildResponse({ ...baseField, type: 'multiselect', value: '' }),
      'node-sdk',
    )
    expect(install.applicationSettings[0]?.value).toEqual([])
  })

  it('keeps a selectbox value as a plain string', () => {
    const install = mapApiInstallToComponent(
      buildResponse({ ...baseField, type: 'selectbox', value: 'id-1' }),
      'node-sdk',
    )
    expect(install.applicationSettings[0]?.value).toBe('id-1')
  })
})

describe('groupKeyOf', () => {
  const setting = (partial: Partial<ApplicationSetting>): ApplicationSetting =>
    partial as ApplicationSetting

  it('prefers formKey when present', () => {
    expect(groupKeyOf(setting({ formKey: 'productsForm', tab: 'Products' }))).toBe('productsForm')
  })

  it('falls back to tab when formKey is absent', () => {
    expect(groupKeyOf(setting({ tab: 'Authorization' }))).toBe('Authorization')
  })

  it("falls back to 'general' when neither formKey nor tab is set", () => {
    expect(groupKeyOf(setting({}))).toBe('general')
  })
})
