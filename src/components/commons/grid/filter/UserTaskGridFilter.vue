<template>
  <v-row dense>
    <v-col cols="2">
      <app-input v-model="userTaskFilter[0][0].value" hide-details clearable label="Node" />
    </v-col>

    <v-col class="my-auto">
      <app-button :on-click="fetchGrid" :button-title="$t('button.filter')" />
      <app-button flat icon :on-click="resetFilter">
        <template #icon>
          <v-icon> mdi-close </v-icon>
        </template>
      </app-button>
    </v-col>
  </v-row>
</template>

<script>
import AppInput from '@/components/commons/input/AppInput'
import AppButton from '@/components/commons/button/AppButton'
import { OPERATOR } from '@/services/enums/gridEnums'
export default {
  name: 'UserTaskGridFilter',
  components: { AppButton, AppInput },
  data() {
    return {
      userTaskFilter: [
        [
          {
            column: 'nodeName',
            operator: OPERATOR.LIKE,
            value: '',
          },
        ],
      ],
    }
  },
  methods: {
    fetchGrid() {
      this.$emit('fetchGrid', { filter: this.userTaskFilter })
    },
    resetFilter() {
      this.userTaskFilter[0][0].value = ''

      this.$emit('fetchGrid')
    },
  },
}
</script>

<style scoped></style>
