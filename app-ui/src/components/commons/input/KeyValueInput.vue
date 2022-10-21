<template>
  <div>
    <v-row v-for="(header, index) in headers" :key="index" dense justify="space-between">
      <v-col cols="5">
        <validation-provider v-slot="{ errors }" :name="$t('form.key')" :rules="fields.input.validations" slim>
          <app-input v-model="header.key" :label="$t('form.key')" :error-messages="errors" />
        </validation-provider>
      </v-col>
      <v-col cols="5">
        <validation-provider v-slot="{ errors }" :name="$t('form.value')" :rules="fields.input.validations" slim>
          <app-input v-model="header.value" :label="$t('form.value')" :error-messages="errors" />
        </validation-provider>
      </v-col>
      <v-col cols="auto">
        <app-button class="ma-auto" small icon :on-click="() => removeLine(index)">
          <template #icon>
            <app-icon>delete</app-icon>
          </template>
        </app-button>
      </v-col>
    </v-row>
    <v-row dense>
      <v-col cols="auto">
        <app-special-button icon="mdi-plus" :disabled="headerAddAbility" @click="addLine" />
      </v-col>
      <v-col class="d-flex justify-start align-center">
        <span>{{ $t('modal.text.headers') }}</span>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import AppInput from '@/components/commons/input/AppInput'
import AppButton from '@/components/commons/button/AppButton'
import AppIcon from '@/components/commons/icon/AppIcon'
import AppSpecialButton from '@/components/commons/button/AppSpecialButton'
export default {
  name: 'KeyValueInput',
  components: { AppSpecialButton, AppIcon, AppButton, AppInput },
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
