<template>
  <ValidationObserver ref="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('folders.createFolder.form.name.name')"
      :rules="fields.name.validations"
      slim
    >
      <app-input
        v-model="form.name"
        :label="$t('folders.createFolder.form.name.label')"
        type="text"
        :error-messages="errors"
      />
    </validation-provider>
  </ValidationObserver>
</template>

<script>
import FormMixin from '../../../commons/mixins/FormMixin'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'FolderForm',
  components: { AppInput },
  mixins: [FormMixin],
  data() {
    return {
      form: {
        name: '',
        parent: '',
      },
      fields: {
        name: {
          id: 'name',
          validations: {
            required: true,
          },
        },
      },
    }
  },
  props: {
    callbackData: {
      type: Object,
      default: () => {},
    },
  },
  methods: {
    async submit() {
      const isValid = await this.$refs.form.validate()
      if (!isValid) {
        return
      }
      this.onSubmit(this.form)
    },
  },
  watch: {
    callbackData: {
      deep: true,
      immediate: true,
      handler(callbackdata) {
        if (!callbackdata) return
        this.form.name = callbackdata.name
        this.form.parent = callbackdata.parent
      },
    },
  },
}
</script>
