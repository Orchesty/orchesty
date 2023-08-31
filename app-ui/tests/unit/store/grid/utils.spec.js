import { prepareGridData, prepareSorter } from "@/services/utils/gridUtils"
import { DIRECTION } from "@/services/enums/gridEnums"
import { createDefaultGridState } from "@/store/modules/grid/state"

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
      itemsPerPage: 50,
    },
    sorter: [
      {
        column: "id",
        direction: DIRECTION.DESCENDING,
      },
    ],
    search: "",
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
      itemsPerPage: 50,
    },
    sorter: null,
    search: "",
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
    search: "",
    params: undefined,
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
    search: "",
    params: undefined,
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
      itemsPerPage: 50,
    },
    params: undefined,
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
      itemsPerPage: 50,
    },
    params: undefined,
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
      itemsPerPage: 50,
    },
    params: undefined,
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
