<template>
  <ValidationObserver ref="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('folders.createFolder.form.name.name')"
      :rules="fields.name.validations"
      slim
    >
      <v-text-field
        v-model="form.name"
        :label="$t('folders.createFolder.form.name.label')"
        type="text"
        :name="fields.name.id"
        :error-messages="errors[0]"
        autofocus
      />
    </validation-provider>
  </ValidationObserver>
</template>

<script>
import FormMixin from '../../../commons/mixins/FormMixin'

export default {
  name: 'CreateFolderForm',
  mixins: [FormMixin],
  data() {
    return {
      form: {
        ...this.init(),
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
    data: {
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
    init(data) {
      if (!data) data = {}
      return {
        name: data.name || null,
        parent: data.parent || null,
      }
    },
    reset() {
      this.form = this.init()
    },
  },
  watch: {
    data: {
      immediate: true,
      handler(val) {
        this.form = this.init(val)
      },
    },
    deep: true,
  },
}
</script>
