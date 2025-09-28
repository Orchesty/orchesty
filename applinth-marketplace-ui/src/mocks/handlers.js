import { http } from "msw"

import auth from "@/api/endpoints/auth"
import overview from "@/api/endpoints/overview"
import appStore from "@/api/endpoints/appStore"
import trash from "@/api/endpoints/trash"
import settings from "@/api/endpoints/settings"
import logs from "@/api/endpoints/logs"

const base = "/api/applinth"

const routeDomains = [auth, overview, appStore, trash, settings, logs]

console.groupCollapsed("MSW routes")
const generatedRoutes = routeDomains
  .flatMap((domain) => Object.values(domain))
  .filter((route) => route.urlPattern)
  .map((route) => {
    const method = route.request({}).method.toLowerCase()
    return {
      url: route.urlPattern,
      method,
      filename: urlPatternToFileName(route.urlPattern, method),
    }
  })
  .map((route) => {
    const url = `${base}${route.url}`
    console.debug(
      "MSW route handler created for:",
      route.method,
      url,
      `${route.filename}.json`
    )
    return http[route.method](url, async (req, res, ctx) => {
      const data = await import(`./api/${route.filename}.json`)
      return res(ctx.status(200), ctx.json(data))
    })
  })

const customRoutes = [
  getDetail("application/shopify"),
  getDetail("application/shoptet"),
  getDetail("application/woocommerce"),
  getDetail("application/s3"),
  getDetail("application/s3/preview"),
]
console.groupEnd("MSW routes")

export const handlers = [...customRoutes, ...generatedRoutes]

function urlPatternToFileName(pattern, method) {
  const cleanedPatten = pattern
    .replace(/^\//, "")
    .replace(/\/$/, "")
    .replace(/\//g, "-")
    .replace(":", "")
  return `${cleanedPatten}+${method}`
}

function getDetail(path) {
  const url = `${base}/${path}`
  console.log(
    "MSW route handler created for:",
    "GET",
    url,
    `${path.replace("/", "-")}.json`
  )
  return http.get(url, async (req, res, ctx) => {
    const data = await import(`./api/${path.replace(/\//g, "-")}.json`)
    return res(ctx.status(200), ctx.json(data))
  })
}
