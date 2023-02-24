export function fitIntoScreen(viewer, diagram, margins = [20, 60]) {
  const parser = new DOMParser()
  const xml = parser.parseFromString(diagram, "text/xml")
  let [left, right, bottom, top] = [undefined, 0, 0, 0]
  for (
    let i = 0;
    i < xml.getElementById("BPMNDiagram_1")?.firstElementChild?.children.length;
    i++
  ) {
    let it = xml.getElementById("BPMNDiagram_1")?.firstElementChild?.children[i]
    if (it.nodeName.endsWith("BPMNShape")) {
      it.childNodes.forEach((child) => {
        if (child.nodeName.endsWith("Bounds")) {
          const x = parseInt(child.getAttribute("x"), 10)
          const y = parseInt(child.getAttribute("y"), 10)
          const w = parseInt(child.getAttribute("width") ?? 0, 10)
          const h = parseInt(child.getAttribute("height") ?? 0, 10)

          if (left === undefined) {
            left = x
            right = x + w
            bottom = y + h
            top = y
          } else {
            left = Math.min(left, x)
            right = Math.max(right, x + w)
            bottom = Math.max(bottom, y + h)
            top = Math.min(top, y)
          }
        }
      })
    }
  }

  if (left !== undefined) {
    const canvas = viewer.get("canvas")
    const scaleLimit = [0.7, 1]
    let maxH = viewer._container.clientHeight
    let maxW = viewer._container.clientWidth
    const scale = Math.min(
      scaleLimit[1],
      Math.max(
        Math.min(maxW / (right - left), maxH / (bottom - top)),
        scaleLimit[0]
      )
    )
    maxW = maxW / scale - margins[0]
    maxH = maxH / scale - margins[1]

    canvas.viewbox({
      x:
        maxW > right - left
          ? left - (maxW - right + left) * 0.5
          : left - margins[0],
      y:
        maxH > bottom - top
          ? top - (maxH - bottom + top) * 0.5
          : top - margins[1],
      width: maxW,
      height: maxH,
    })
  }
}
