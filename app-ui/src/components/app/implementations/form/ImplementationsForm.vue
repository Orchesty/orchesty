<template>
  <validation-observer ref="form" tag="form" @submit.prevent="submit">
    <validation-provider v-slot="{ errors }" :name="$t('form.url')" :rules="fields.site.validations" slim>
      <app-input v-model="form.site" autofocus :label="$t('form.url')" :error-messages="errors" />
    </validation-provider>
    <validation-provider v-slot="{ errors }" :name="$t('form.name')" :rules="fields.name.validations" slim>
      <app-input v-model="form.name" :label="$t('form.name')" :error-messages="errors" />
    </validation-provider>
    <key-value-input v-model="form.headers" :headers-prop="form.headers" />
  </validation-observer>
</template>

<script>
import FormMixin from '../../../../services/mixins/FormMixin'
import AppInput from '@/components/commons/input/AppInput'
import KeyValueInput from '@/components/commons/input/KeyValueInput'

export default {
  name: 'ImplementationsForm',
  components: { KeyValueInput, AppInput },
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
        headers: value.headers || [],
        name: value.name || null,
        site: value.url || null,
      }
    },
    resetForm() {
      this.form = {
        headers: [],
        name: null,
        url: null,
      }
      this.$nextTick(() => {
        this.$refs.form.reset()
      })
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
  mounted() {
    this.$refs.form.reset()
  },
}
</script>
