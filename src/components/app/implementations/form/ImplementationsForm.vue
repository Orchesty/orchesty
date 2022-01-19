<template>
  <validation-observer ref="form" tag="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('implementation.form.url.name')"
      :rules="fields.site.validations"
      slim
    >
      <app-input v-model="form.site" :label="$t('implementation.form.url.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('implementation.form.name.name')"
      :rules="fields.name.validations"
      slim
    >
      <app-input v-model="form.name" :label="$t('implementation.form.name.label')" :error-messages="errors" />
    </validation-provider>
  </validation-observer>
</template>

<script>
import FormMixin from '../../../commons/mixins/FormMixin'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'ImplementationsForm',
  components: { AppInput },
  mixins: [FormMixin],
  props: {
    implementation: {
      type: Object,
      default: () => ({}),
      required: false,
    },
  },
  data() {
    return {
      form: {
        ...this.initForm(),
      },
      fields: {
        site: {
          validations: {
            required: true,
            url: [{ require_protocol: true, require_tld: false }, { protocols: ['https', 'http'] }],
          },
        },
        name: {
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
      this.onSubmit(this.form)
    },
    initForm(value) {
      if (!value) value = {}
      return {
        name: value.name || null,
        site: value.url || null,
      }
    },
    resetForm() {
      this.form = {
        name: null,
        url: null,
      }
    },
  },
  watch: {
    implementation: {
      handler(implementation) {
        this.form = this.initForm(implementation)
      },
      immediate: true,
    },
    deep: true,
  },
}
</script>
