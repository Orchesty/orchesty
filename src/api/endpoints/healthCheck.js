import moment from 'moment'
import { OPERATOR } from '@/services/enums/gridEnums'

export default {
  grid: {
    id: 'HEALTH_CHECK_GRID',
    request: (data) => ({
      url: `/metrics/consumers?filter=${JSON.stringify(data)}`,
      method: 'GET',
    }),
    reduce: (data) => {
      return {
        items: data,
        paging: {
          page: 1,
          itemsPerPage: 99999999,
        },
        filter: [
          {
            column: 'created',
            operator: OPERATOR.BETWEEN,
            value: [moment().utc().subtract(1, 'minutes').format(), moment().utc().format()],
          },
        ],
        sorter: null,
      }
    },
  },
}
