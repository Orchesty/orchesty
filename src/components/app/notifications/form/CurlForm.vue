<template>
  <validation-observer ref="form" tag="form" @submit.prevent="submit" @keypress.enter="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('curlSender.form.url.name')"
      :rules="fields.url.validations"
      slim
    >
      <v-text-field v-model="form.url" :label="$t('curlSender.form.url.label')" :error-messages="errors[0]" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('curlSender.form.method.name')"
      :rules="fields.text.validations"
      slim
    >
      <v-select
        v-model="form.method"
        :items="restMethods"
        :label="$t('curlSender.form.method.label')"
        :error-messages="errors[0]"
      />
    </validation-provider>
    <key-value-input v-model="form.headers" :headers-prop="form.headers" />
  </validation-observer>
</template>

<script>
import FormMixin from '@/components/commons/mixins/FormMixin'
import KeyValueInput from '../../../commons/input/KeyValueInput'

export default {
  name: 'CurlForm',
  components: { KeyValueInput },
  mixins: [FormMixin],
  props: {
    service: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      form: {
        ...this.initForm(),
      },
      restMethods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
      fields: {
        url: {
          validations: {
            required: true,
            url: [{ require_protocol: true }, { protocols: ['https', 'http'] }],
          },
        },
        text: {
          validations: {
            required: true,
          },
        },
      },
    }
  },
  methods: {
    async submit() {
      const isValid = await this.$refs.form.validate()
      if (!isValid) {
        return
      }

      return this.onSubmit(this.form)
    },
    initForm(value) {
      if (!value) value = {}
      return {
        method: value.method || null,
        url: value.url || null,
        headers: value.headers || [],
      }
    },
  },
  watch: {
    service: {
      immediate: true,
      deep: true,
      handler() {
        this.form = this.initForm(this.service.settings)
      },
    },
  },
}
</script>

<style scoped></style>
