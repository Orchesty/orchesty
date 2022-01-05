<script>
import { OPERATOR } from '@/store/grid'
import { DATE_FILTERS } from '@/services/filters'

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
                value: DATE_FILTERS.LAST_5_MINS(),
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
                value: DATE_FILTERS.LAST_30_MINS(),
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
                value: DATE_FILTERS.LAST_HOUR(),
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
                value: DATE_FILTERS.LAST_6_HOURS(),
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
                value: DATE_FILTERS.LAST_24_HOURS(),
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
