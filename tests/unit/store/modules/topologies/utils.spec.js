import { createTree } from "../../../../../src/store/modules/topologies/utils"

test("create tree - empty", () => {
  expect(createTree([], [])).toEqual([])
})

test("create tree", () => {
  const topologies = [
    {
      _id: "5fc682f1dcf8cd271e3926f2",
      type: "webhook",
      name: "topology-1",
      descr: "description",
      status: "New",
      visibility: "draft",
      version: 1,
      category: null,
      enabled: true,
    },
    {
      _id: "5fc68325bb19025743374815",
      type: "webhook",
      name: "topology-2",
      descr: "description",
      status: "New",
      visibility: "draft",
      version: 1,
      category: "5fc682fc5c1656652b0ec426",
      enabled: true,
    },
    {
      _id: "5fc68348f8960602c26cf313",
      type: "webhook",
      name: "topology-3",
      descr: "description",
      status: "New",
      visibility: "draft",
      version: 1,
      category: "5fc6830717eeeb7d550c4a96",
      enabled: true,
    },
  ]

  const categories = [
    { _id: "5fc682fc5c1656652b0ec426", name: "Folder #1", parent: null },
    {
      _id: "5fc6830717eeeb7d550c4a96",
      name: "Folder #2",
      parent: "5fc682fc5c1656652b0ec426",
    },
    {
      _id: "5fc6830d5c1656652b0ec427",
      name: "Folder #3",
      parent: "5fc6830717eeeb7d550c4a96",
    },
    { _id: "5fc6832fb495161c6e5eab46", name: "Folder #4", parent: null },
  ]

  const result = [
    {
      id: "5fc682fc5c1656652b0ec426",
      name: "Folder #1",
      type: "CATEGORY",
      parent: null,
      children: [
        {
          id: "5fc6830717eeeb7d550c4a96",
          name: "Folder #2",
          type: "CATEGORY",
          parent: "5fc682fc5c1656652b0ec426",
          children: [
            {
              id: "5fc6830d5c1656652b0ec427",
              name: "Folder #3",
              type: "CATEGORY",
              parent: "5fc6830717eeeb7d550c4a96",
              children: [],
            },
            {
              id: "5fc68348f8960602c26cf313",
              name: "topology-3",
              type: "TOPOLOGY",
              parent: undefined,
              description: "description",
            },
          ],
        },
        {
          id: "5fc68325bb19025743374815",
          name: "topology-2",
          type: "TOPOLOGY",
          parent: undefined,
          description: "description",
        },
      ],
    },
    {
      id: "5fc6832fb495161c6e5eab46",
      name: "Folder #4",
      type: "CATEGORY",
      parent: null,
      children: [],
    },
    {
      id: "5fc682f1dcf8cd271e3926f2",
      name: "topology-1",
      type: "TOPOLOGY",
      parent: undefined,
      description: "description",
    },
  ]

  expect(createTree(topologies, categories)).toEqual(result)
})
