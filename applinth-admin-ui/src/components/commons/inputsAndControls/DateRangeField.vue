<template>
  <v-date-picker v-model="dates" range />
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import { ValidationProvider } from "vee-validate"
import { toLocalDate } from "../../../filters/datetime"

@Component({
  components: {
    ValidationProvider,
  },
  filters: {
    toLocalDate,
  },
})
export default class DateField extends Vue {
  @Prop({
    required: false,
    type: [String, String],
    default: ["2019-09-10", "2019-09-20"],
  })
  dates!: [string, string]
}
</script>
<style lang="scss" scoped></style>
