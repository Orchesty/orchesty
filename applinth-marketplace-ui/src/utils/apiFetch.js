import axios from "axios"
import { config } from "@/config"
import { authService } from "@/utils/authService.js"
import showFlashMessage from "@/utils/flashMessage"
import { FLASH_MESSAGES_TYPES } from "@/store/flashMessages/types"
import { i18n } from "@/localization"
import { API } from "@/api"

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

  let authorization = { Authorization: authService.accessToken }
  // Do not send authorization header if the route relies on cookies
  if (config.authorization === false) {
    authorization = {}
  }

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
          ...authorization,
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

        const isRefreshUrl =
          err.config?.url === API.auth.refreshAuth.request().url
        const isFailedAuthRequest = err.response?.status === 401

        if (isRefreshUrl && isFailedAuthRequest) {
          // Without access token this first request to refresh auth fails, but
          // the subsequent login attempt will pass if an initialization token
          // is provided and valid.
          if (throwError) {
            reject(err)
          }
          return
        }

        if (isFailedAuthRequest) {
          showFlashMessage(
            i18n.t("flashMessage.authenticationExpired"),
            FLASH_MESSAGES_TYPES.ERROR
          )
        } else {
          showFlashMessage(
            i18n.t("flashMessage.apiError"),
            FLASH_MESSAGES_TYPES.ERROR
          )
        }

        if (throwError) {
          reject(err)
        }
      })
  })
}

const callApi = (params) =>
  call(params, (config) => send({ ...config, withCredentials: true }))

export { callApi }
