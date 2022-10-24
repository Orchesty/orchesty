export const formatNumber = (val: number): string => {
  return new Intl.NumberFormat().format(val)
}

export const formatPercent = (val: number): string => {
  val = val / 100
  return val.toLocaleString(undefined, {
    style: "percent",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })
}
