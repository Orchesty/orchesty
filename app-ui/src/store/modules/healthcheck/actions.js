import { callApi } from '../../utils'
import { API } from '../../../api'
import { HEALTHCHECK } from '@/store/modules/healthcheck/types'

export default {
  [HEALTHCHECK.ACTIONS.FETCH_ERROR_LIST]: async ({ dispatch }, payload) => {
    try {
      const items = await callApi(dispatch, {
        requestData: { ...API.healthCheck.errorList },
        params: {
          ...payload,
        },
      })

      return items
    } catch {
      return null
    }
  },
}
