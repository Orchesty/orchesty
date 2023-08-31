import { clearFilter, getValue, removeFilter, upsertFilter } from '@/services/utils/gridFilters'

test('Filters::upsertFilter - default', () => {
  const filters = []

  upsertFilter(filters, 0, 0, { column: 'column1', operator: 'EQUAL', value: 'value' })

  const result = [
    [
      {
        column: 'column1',
        operator: 'EQUAL',
        value: 'value',
      },
    ],
  ]

  expect(filters).toEqual(result)
})

test('Filters::removeFilter - default', () => {
  const filters = [
    [
      {
        column: 'column1',
        operator: 'EQUAL',
        value: 'value',
      },
      {
        column: 'column1',
        operator: 'EQUAL',
        value: 'value',
      },
    ],
  ]

  removeFilter(filters, 0, 1)

  const result = [
    [
      {
        column: 'column1',
        operator: 'EQUAL',
        value: 'value',
      },
    ],
  ]

  expect(filters).toEqual(result)
})

test('Filters::getValue - default', () => {
  expect(getValue([])).toEqual(null)
  expect(getValue(null)).toEqual(null)
  expect(getValue(['value'])).toEqual('value')
})

test('Filters::clear - default', () => {
  const filters = [
    [
      {
        column: 'column1',
        operator: 'EQUAL',
        value: [undefined],
      },
      {
        column: 'column1',
        operator: 'EQUAL',
        value: [null],
      },
    ],
    [],
    [
      {
        column: 'column1',
        operator: 'EQUAL',
        value: [false],
      },
    ],
  ]

  const result = [
    [
      {
        column: 'column1',
        operator: 'EQUAL',
        value: [false],
      },
    ],
  ]

  expect(clearFilter(filters)).toEqual(result)
})
