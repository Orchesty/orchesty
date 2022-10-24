import { TOPOLOGY_ENUMS } from "@/services/enums/topologyEnums"

let tmpTop = null

export const createTree = (topologies = [], categories = []) => {
  tmpTop = new Map()

  topologies.forEach((item) => {
    const id = item.category
    if (!tmpTop.has(id)) {
      tmpTop.set(id, [
        {
          ...item,
          id: item._id,
          type: TOPOLOGY_ENUMS.TOPOLOGY,
        },
      ])
    } else {
      tmpTop.set(id, [
        ...tmpTop.get(id),
        {
          ...item,
          id: item._id,
          type: TOPOLOGY_ENUMS.TOPOLOGY,
        },
      ])
    }
  })

  let tree = []

  const roots = findCategory(categories, null)

  roots.forEach((item) => {
    const newItem = {
      _id: item._id,
      id: item._id,
      name: item.name,
      type: TOPOLOGY_ENUMS.CATEGORY,
      parent: item.parent,
    }
    tree.push(newItem)

    recursive(categories, findCategory(categories, item._id), newItem)
  })

  if (tmpTop.has(null)) {
    tree = [...tree, ...tmpTop.get(null)]
  }

  return tree
}

const findCategory = (categories, id) => {
  return categories.filter((item) => item.parent === id)
}

const recursive = (categories, items, parent) => {
  parent.children = []
  items.forEach((item) => {
    const newItem = {
      id: item._id,
      name: item.name,
      type: TOPOLOGY_ENUMS.CATEGORY,
      parent: item.parent,
    }
    parent.children.push(newItem)

    recursive(categories, findCategory(categories, item._id), newItem)
  })

  if (tmpTop.has(parent.id)) {
    parent.children = [...parent.children, ...tmpTop.get(parent.id)]
  }
}
