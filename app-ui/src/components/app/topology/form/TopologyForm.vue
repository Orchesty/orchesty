<template>
  <ValidationObserver ref="form" tag="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('form.name')"
      :rules="fields.name.validations"
      slim
    >
      <app-input
        v-model="form.name"
        autofocus
        :disabled="
          callbackData
            ? callbackData.visibility === PAGE_TABS_ENUMS.PUBLIC
            : false
        "
        :label="$t('form.name')"
        type="text"
        :error-messages="errors"
      />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('form.description')"
      :rules="fields.description.validations"
      slim
    >
      <app-input
        v-model="form.description"
        :label="$t('form.description')"
        type="text"
        :error-messages="errors"
      />
    </validation-provider>
  </ValidationObserver>
</template>

<script>
import FormMixin from "../../../../services/mixins/FormMixin"
import { TOPOLOGY_ENUMS } from "@/services/enums/topologyEnums"
import AppInput from "@/components/commons/input/AppInput"

export default {
  name: "TopologyForm",
  components: { AppInput },
  mixins: [FormMixin],
  data() {
    return {
      PAGE_TABS_ENUMS: TOPOLOGY_ENUMS,
      form: {
        name: "",
        description: "",
        folder: "",
      },
      fields: {
        name: {
          id: "name",
          validations: {
            required: true,
          },
        },
        description: {
          id: "description",
          validations: {},
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
      if (
        this.callbackData &&
        this.callbackData.visibility === TOPOLOGY_ENUMS.PUBLIC
      ) {
        this.onSubmit({
          description: this.form.description,
          folder: this.form.folder,
        })
      } else {
        this.onSubmit(this.form)
      }
    },
  },
  watch: {
    callbackData: {
      deep: true,
      immediate: true,
      handler(callbackdata) {
        if (!callbackdata) return
        this.form.name = callbackdata.name
        this.form.description = callbackdata.description
        this.form.folder = callbackdata.folder

        this.$nextTick(() => {
          this.$refs.form.reset()
        })
      },
    },
  },
}
</script>
