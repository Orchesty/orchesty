<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-autocomplete
      v-model="innerValue"
      :items="items"
      :loading="state.isSending"
      :search-input.sync="search"
      :deletable-chips="true"
      :label="label"
      :multiple="multiple"
      :clearable="clearable || !multiple"
      outlined
      dense
      :small-chips="multiple"
      :error-messages="errors[0]"
      :hide-details="valid"
      @focus.once="onFocus"
    />
  </validation-provider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'
import { REQUESTS_STATE } from '../../../../../store/modules/api/types'
import { mapGetters } from 'vuex'

export default {
  name: 'AutoCompleteInput',
  components: { ValidationProvider },
  props: {
    label: {
      type: String,
      required: true,
    },
    column: {
      type: String,
      required: true,
    },
    value: {
      type: Array,
      required: false,
      default: () => null,
    },
    requestId: {
      type: String,
      required: true,
    },
    onSearch: {
      type: Function,
      required: true,
    },
    onChange: {
      type: Function,
      required: true,
    },
    clearable: {
      type: Boolean,
      default: false,
    },
    rules: {
      type: [Object, String],
      default: '',
    },
    multiple: {
      type: Boolean,
      default: true,
    },
    requestOnFocus: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([this.requestId])
    },
  },
  data() {
    return {
      items: this.value,
      search: null,
      innerValue: null,
      searchTimeoutId: null,
    }
  },
  methods: {
    onFocus() {
      if (this.requestOnFocus) {
        this.requestData()
      }
    },
    async requestData(val = '') {
      this.items = await this.onSearch(val)
    },
  },
  watch: {
    value(newVal) {
      if (this.multiple) {
        this.innerValue = newVal
      } else {
        if (newVal[0]) {
          this.innerValue = newVal[0]
        } else {
          this.innerValue = null
        }
      }
    },
    innerValue() {
      this.onChange(this.innerValue)
    },
    search(val) {
      if (!val) {
        return
      }

      clearTimeout(this.searchTimeoutId)
      this.searchTimeoutId = setTimeout(() => {
        this.requestData(val)
      }, 300)
    },
  },
}
</script>
