<template>
  <div class="data-table">
    <SimpleList
      :headers="headers"
      item-key="name"
      :items="items"
      :loading="loading"
      no-data-text="healthcheck.list.itWorks"
    >
      <template #no-data>
        <p>The Table is Empty. Please insert data with the above Button.</p>
      </template>
      <template #name="{ item }">
        <div class="d-flex align-center py-2">
          <v-icon large color="red" class="mr-3"> warning </v-icon>
          <div>
            <template v-if="item.type === 'queue'">
              <strong>{{ $t('healthcheck.list.errorQueueItemTitle', { name: item.name }) }} </strong> <br />
              <span class="text-sm-body-2">
                {{ $t('healthcheck.list.errorQueueItemText', { service: item.service, topology: item.topology }) }}
              </span>
            </template>
            <template v-else>
              <strong>{{ $t('healthcheck.list.errorServiceItem', { name: item.name }) }} </strong> <br />
              <span class="text-sm-body-2">
                {{ $t('healthcheck.list.errorServiceItemText', { name: item.name }) }}
              </span>
            </template>
          </div>
        </div>
      </template>
    </SimpleList>
  </div>
</template>

<script>
import SimpleList from '@/components/commons/grid/SimpleList'
import { mapActions } from 'vuex'
import { HEALTHCHECK } from '@/store/modules/healthcheck/types'
export default {
  name: 'HealthCheckErrors',
  components: { SimpleList },
  data() {
    return {
      headers: [
        {
          value: 'name',
        },
      ],
      items: [],
      loading: false,
    }
  },
  async created() {
    this.fetchItems()
  },
  methods: {
    ...mapActions(HEALTHCHECK.NAMESPACE, [HEALTHCHECK.ACTIONS.FETCH_ERROR_LIST]),
    async fetchItems() {
      const response = await this[HEALTHCHECK.ACTIONS.FETCH_ERROR_LIST]()

      if (response && response.items) {
        this.items = response.items
      }
    },
  },
}
</script>

<style scoped lang="scss">
.data-table {
  ::v-deep tr {
    cursor: default;
  }
}
</style>
