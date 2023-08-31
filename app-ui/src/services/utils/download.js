export default function (content, fileName, mimeType) {
  const a = document.createElement("a")
  mimeType = mimeType || "application/octet-stream"

  if (navigator.msSaveBlob) {
    // IE10
    return navigator.msSaveBlob(
      new Blob([content], { type: mimeType }),
      fileName
    )
  } else if ("download" in a) {
    // html5 A[download]
    a.href = `data:${mimeType},${encodeURIComponent(content)}`
    a.setAttribute("download", fileName)
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    return true
  } // do iframe dataURL download (old ch+FF):
  const f = document.createElement("iframe")
  document.body.appendChild(f)
  f.src = `data:${mimeType},${encodeURIComponent(content)}`

  setTimeout(() => {
    document.body.removeChild(f)
  }, 333)
  return true
}
