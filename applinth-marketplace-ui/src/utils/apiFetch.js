import axios from "axios"
import { config } from "@/config"
import { authService } from "@/utils/authService.js"

const apiClient = axios.create({
  baseURL: `${config.backend.apiBaseUrl}/api/applinth`,
  withCredentials: true,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json; charset=utf-8",
  },
})

const send = (config) => {
  const { method, body, url } = config

  const headers = config.headers || {}

  return new Promise((resolve, reject) => {
    if (!method) {
      reject(new Error("The request must have method."))
    }

    if (!url) {
      reject(new Error("The request must have url."))
    }

    resolve(
      apiClient.request({
        ...config,
        url,
        headers: {
          Authorization: authService.accessToken,
          ...headers,
        },
        body: JSON.stringify(body),
      })
    )
  })
}

const call = ({ requestData, params = null, throwError = true }, sender) => {
  const { id, request, mock, reduce } = requestData

  if (!id) {
    throw new Error("Request must have id.")
  }

  if (mock) {
    const renderedRequest = request(params)
    console.log(
      `App using mock request id=[${id}], instead of a ${renderedRequest.method}: ${renderedRequest.url}`
    )

    if (reduce) {
      return reduce(mock())
    }

    return mock()
  }

  return new Promise((resolve, reject) => {
    return sender(request(params))
      .then((res) => {
        if (reduce) {
          resolve(reduce(res.data))
        }

        resolve(res.data)
      })
      .catch((err) => {
        console.error("Response ERROR!", err)

        if (throwError) {
          reject(err)
        }
      })
  })
}

const callApi = (params) =>
  call(params, (config) => send({ ...config, withCredentials: true }))

export { callApi }
