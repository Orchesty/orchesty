<script>
import { OPERATOR } from '@/services/enums/gridEnums'
import { QUICK_FILTERS } from '@/services/utils/quickFilters'

export default {
  name: 'QuickFiltersMixin',
  methods: {
    init(val) {
      this.quickFilters = [
        {
          name: 'enums.duration.last5mins',
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_5_MINS(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: 'enums.duration.last30mins',
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_30_MINS(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: 'enums.duration.lastHour',
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_HOUR(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: 'enums.duration.last6hours',
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_6_HOURS(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: 'enums.duration.last24hours',
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_24_HOURS(),
                isQuickFilter: true,
              },
            ],
          ],
        },
      ]
    },
  },
  data() {
    this.column = ''
    return {
      quickFilters: [],
    }
  },
}
</script>
