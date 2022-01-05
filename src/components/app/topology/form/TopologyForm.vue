<template>
  <ValidationObserver ref="form" tag="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('topologies.create.form.name.label')"
      :rules="fields.name.validations"
      slim
    >
      <v-text-field
        v-model="form.name"
        :disabled="data.visibility === PAGE_TABS_ENUMS.PUBLIC || data.version > 1"
        :label="$t('topologies.create.form.name.label')"
        type="text"
        :error-messages="errors[0]"
        autofocus
      />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('topologies.create.form.description.label')"
      :rules="fields.description.validations"
      slim
    >
      <v-text-field
        v-model="form.description"
        :label="$t('topologies.create.form.description.label')"
        type="text"
        :error-messages="errors[0]"
      />
    </validation-provider>
  </ValidationObserver>
</template>

<script>
import FormMixin from '../../../commons/mixins/FormMixin'
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'

export default {
  name: 'CreateTopologyForm',
  mixins: [FormMixin],
  data() {
    return {
      PAGE_TABS_ENUMS: TOPOLOGY_ENUMS,
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
        description: {
          id: 'description',
          validations: {},
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
      if (this.data && this.data.visibility === TOPOLOGY_ENUMS.PUBLIC) {
        this.onSubmit({ descr: this.form.description, folder: this.form.folder })
      } else {
        this.onSubmit(this.form)
      }
    },
    init(data) {
      if (!data) data = {}
      return {
        name: data.name || null,
        description: data.description || null,
        folder: data.category || null,
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
