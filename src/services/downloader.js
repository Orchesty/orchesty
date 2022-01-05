export const blobToUtf8 = (data) => {
  return [
    new Uint8Array([0xef, 0xbb, 0xbf]), // UTF-8 BOM - https://en.wikipedia.org/wiki/Byte_order_mark
    data,
  ]
}

export const stringToUtf8 = (data) => {
  const bom = '\ufeff' // UTF-8 BOM - https://en.wikipedia.org/wiki/Byte_order_mark

  return bom + data
}

export const downloader = (data, fileName, type = 'text/plain') => {
  const link = document.createElement('a')
  link.href = window.URL.createObjectURL(new Blob([data], { type }))
  link.target = '_blank'
  link.setAttribute('download', fileName)
  document.body.appendChild(link)
  link.click()
}
