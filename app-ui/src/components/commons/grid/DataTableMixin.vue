<script>
export default {
  name: 'DataTableMixin',
  computed: {
    visibleHeaders() {
      return this.headers.filter((header) => header.visible || header.alwaysVisible === true)
    },
  },
  methods: {
    isItemVisible(name) {
      const index = this.headers.findIndex((item) => item.value === name)

      if (index !== -1) {
        if (this.headers[index].alwaysVisible === true) {
          return true
        }

        return this.headers[index].visible
      }

      return true
    },
    expand(item) {
      item.expand(!item.isExpanded)
    },
    findFilter(key, filters) {
      if (Object.prototype.hasOwnProperty.call(filters, key)) {
        return filters[key]
      }

      return null
    },
  },
}
</script>
