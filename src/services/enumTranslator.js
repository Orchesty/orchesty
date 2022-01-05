import { i18n } from '../localization'

export const tEnumOptions = (enumObject, key) => {
  const res = []
  Object.values(enumObject).forEach((item) => {
    res.push({ text: i18n.t(`${key}.${item}`), value: item })
  })

  return res
}

export const tEnumSingle = (item, key) => {
  return i18n.t(`${key}.${item}`)
}

export const tEnum = (enumObject, key) => {
  const res = []
  Object.values(enumObject).forEach((item) => {
    res.push(i18n.t(`${key}.${item}`))
  })

  return res
}
