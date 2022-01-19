<template>
  <div>
    <v-row v-for="(header, index) in headers" :key="index" justify="space-between">
      <v-col cols="5">
        <validation-provider
          v-slot="{ errors }"
          :name="$t('form.keyValue.key.name')"
          :rules="fields.input.validations"
          slim
        >
          <app-input v-model="header.key" :label="$t('form.keyValue.key.label')" :error-messages="errors" />
        </validation-provider>
      </v-col>
      <v-col cols="5">
        <validation-provider
          v-slot="{ errors }"
          :name="$t('form.keyValue.value.name')"
          :rules="fields.input.validations"
          slim
        >
          <app-input v-model="header.value" :label="$t('form.keyValue.value.label')" :error-messages="errors" />
        </validation-provider>
      </v-col>
      <v-col cols="auto">
        <v-btn color="red" class="ma-auto" dark small fab @click="removeLine(index)">
          <v-icon>mdi-trash-can</v-icon>
        </v-btn>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="auto">
        <v-btn class="mt-1" color="primary" small fab :disabled="headerAddAbility" @click="addLine">
          <v-icon>mdi-plus</v-icon>
        </v-btn>
      </v-col>
      <v-col class="d-flex justify-start align-center">
        <span>{{ $t('notifications.headers') }}</span>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import AppInput from '@/components/commons/input/AppInput'
export default {
  name: 'KeyValueInput',
  components: { AppInput },
  props: {
    keyLabel: {
      type: String,
      default: '',
    },
    headersProp: {
      type: Array,
      default: () => [],
    },
    valueLabel: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      headers: [],
      fields: {
        input: {
          validations: {
            required: true,
          },
        },
      },
    }
  },
  computed: {
    headerAddAbility() {
      let checkEmptyHeaders = this.headers.filter(
        (header) => header.key === '' || header.value === '' || header.key === null || header.value === null
      )
      return checkEmptyHeaders.length >= 1 && this.headers.length > 0
    },
  },
  methods: {
    addLine() {
      let checkEmptyHeaders = this.headers.filter(
        (header) => header.key === '' || header.value === '' || header.key === null || header.value === null
      )
      if (checkEmptyHeaders.length >= 1 && this.headers.length > 0) {
        return
      }
      this.headers.push({
        value: null,
        key: null,
      })
    },
    removeLine(lineId) {
      this.headers.splice(lineId, 1)
    },
  },
  watch: {
    headers: {
      immediate: true,
      deep: true,
      handler() {
        this.$emit('input', this.headers)
      },
    },
    headersProp: {
      immediate: true,
      deep: true,
      handler(value) {
        this.headers = value
      },
    },
  },
}
</script>
