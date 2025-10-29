<script>
import { OPERATOR } from "@/services/enums/gridEnums"
import { QUICK_FILTERS } from "@/services/utils/quickFilters"

export default {
  name: "QuickFiltersMixin",
  methods: {
    init(val) {
      this.quickFilters = [
        {
          name: "button.last5mins",
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
          name: "button.last30mins",
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
          name: "button.lastHour",
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
          name: "button.last6hours",
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
          name: "button.last24hours",
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
    initRuns(val) {
      this.quickFilters = [
        {
          name: "button.all",
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.ALL_RUN(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: "button.last1runs",
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_1_RUN(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: "button.last10runs",
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_10_RUN(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: "button.last100runs",
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_100_RUN(),
                isQuickFilter: true,
              },
            ],
          ],
        },
        {
          name: "button.last1000runs",
          filter: [
            [
              {
                column: val,
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_1000_RUN(),
                isQuickFilter: true,
              },
            ],
          ],
        },
      ]
    },
  },
  data() {
    this.column = ""
    return {
      quickFilters: [],
    }
  },
}
</script>
