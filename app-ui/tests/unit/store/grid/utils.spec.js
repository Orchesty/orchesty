import {
  prepareGridData,
  prepareGridHeaderStateForSave,
  prepareGridStateForSave,
  prepareSorter,
} from "@/services/utils/gridUtils"
import { DIRECTION, OPERATOR } from "@/services/enums/gridEnums"
import { createDefaultGridState } from "@/store/modules/grid/state"
import { FILTER } from "@/services/utils/gridUtils"

// PREPARE SORTER
test("Grid::prepareSorter - null", () => {
  expect(prepareSorter(null, null)).toEqual(null)
})

test("Grid::prepareSorter - column and direction null", () => {
  const stateSorter = [
    {
      column: "id",
      direction: "ascending",
    },
  ]

  const payloadSorter = [
    {
      column: null,
      direction: null,
    },
  ]

  const result = null

  expect(prepareSorter(stateSorter, payloadSorter)).toEqual(result)
})

test("Grid::prepareSorter - default", () => {
  const stateSorter = [
    {
      column: "id",
      direction: "ascending",
    },
  ]

  const payloadSorter = [
    {
      column: "created",
      direction: "descending",
    },
  ]

  const result = [
    {
      column: "created",
      direction: "descending",
    },
  ]

  expect(prepareSorter(stateSorter, payloadSorter)).toEqual(result)
})

// GRID STATE
test("Grid::prepareData - default", () => {
  const state = createDefaultGridState("test")

  const result = {
    filter: [],
    paging: {
      page: 1,
      itemsPerPage: 10,
    },
    sorter: [
      {
        column: "id",
        direction: DIRECTION.DESCENDING,
      },
    ],
    search: null,
  }

  expect(prepareGridData(state)).toEqual(result)
})

test("Grid::prepareData - null sorter", () => {
  const state = createDefaultGridState("test")
  state.sorter = null

  const result = {
    filter: [],
    paging: {
      page: 1,
      itemsPerPage: 10,
    },
    sorter: null,
    search: null,
  }

  expect(prepareGridData(state)).toEqual(result)
})

test("Grid::prepareData - paging, sorter", () => {
  const state = createDefaultGridState("test")

  const payload = {
    paging: {
      page: 2,
      itemsPerPage: 15,
    },
    sorter: [
      {
        column: "created",
        direction: DIRECTION.ASCENDING,
      },
    ],
  }

  const result = {
    filter: [],
    paging: {
      page: 2,
      itemsPerPage: 15,
    },
    sorter: [
      {
        column: "created",
        direction: DIRECTION.ASCENDING,
      },
    ],
    search: null,
  }

  expect(prepareGridData(state, payload)).toEqual(result)
})

test("Grid::prepareData - paging, sorter null", () => {
  const state = createDefaultGridState("test")

  const payload = {
    paging: {
      page: 2,
      itemsPerPage: 15,
    },
    sorter: null,
  }

  const result = {
    filter: [],
    paging: {
      page: 2,
      itemsPerPage: 15,
    },
    sorter: null,
    search: null,
  }

  expect(prepareGridData(state, payload)).toEqual(result)
})

test("Grid::prepareData - search", () => {
  const state = createDefaultGridState("test")
  state.search = "test"

  const payload = {
    search: "value",
  }

  const result = {
    filter: [],
    paging: {
      page: 1,
      itemsPerPage: 10,
    },
    sorter: [
      {
        column: "id",
        direction: DIRECTION.DESCENDING,
      },
    ],
    search: "value",
  }

  expect(prepareGridData(state, payload)).toEqual(result)
})

test("Grid::prepareData - delete null", () => {
  const state = createDefaultGridState("test")
  state.search = "test"

  const payload = {
    search: null,
  }

  const result = {
    filter: [],
    paging: {
      page: 1,
      itemsPerPage: 10,
    },
    sorter: [
      {
        column: "id",
        direction: DIRECTION.DESCENDING,
      },
    ],
    search: null,
  }

  expect(prepareGridData(state, payload)).toEqual(result)
})

test("Grid::prepareData - delete empty string", () => {
  const state = createDefaultGridState("test")
  state.search = "test"

  const payload = {
    search: "",
  }

  const result = {
    filter: [],
    paging: {
      page: 1,
      itemsPerPage: 10,
    },
    sorter: [
      {
        column: "id",
        direction: DIRECTION.DESCENDING,
      },
    ],
    search: "",
  }

  expect(prepareGridData(state, payload)).toEqual(result)
})

// create state for save
test("Grid::prepareGridStateForSave", () => {
  const state = createDefaultGridState("test")
  state.search = "test"
  state.filter = [
    [{ column: "test", operator: OPERATOR.EQUAL, values: ["test"] }],
  ]
  state.filterMeta = { type: FILTER.SIMPLE_FILTER }

  const result = {
    filter: [[{ column: "test", operator: OPERATOR.EQUAL, values: ["test"] }]],
    filterMeta: { type: FILTER.SIMPLE_FILTER },
    sorter: [
      {
        column: "id",
        direction: "DESCENDING",
      },
    ],
    version: "1.0.0",
  }

  expect(prepareGridStateForSave(state)).toEqual(result)
})

// create state for save
test("Grid::prepareGridStateForSave", () => {
  const state = createDefaultGridState("test")
  state.headersMeta = [{ value: "id", visible: true }]

  const result = {
    headersMeta: [{ value: "id", visible: true }],
    version: "1.0.0",
  }

  expect(prepareGridHeaderStateForSave(state)).toEqual(result)
})
