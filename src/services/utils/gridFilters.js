export const upsertFilter = (filters, andIndex, orIndex, filter) => {
  if (!Array.isArray(filters[andIndex])) {
    filters[andIndex] = []
  }

  filters[andIndex][orIndex] = filter

  return [...filters]
}

export const removeFilter = (filters, andIndex, orIndex) => {
  if (Array.isArray(filters[andIndex])) {
    filters[andIndex].splice(orIndex, 1)

    if (!filters[andIndex].length) {
      filters.splice(andIndex, 1)
    }
  }
}

export const clearFilter = (filter) => {
  let clearFilter = filter

  clearFilter = clearFilter.map((and) => {
    return and.filter((orIndex) => {
      return (
        orIndex.value.length >= 1 &&
        orIndex.value.every((value) => value || value === false)
      )
    })
  })

  return clearFilter.filter((and) => {
    return and.length >= 1
  })
}

export const getValue = (value) => {
  if (Array.isArray(value) && value[0]) {
    return value[0]
  }

  return null
}
