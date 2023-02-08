<template>
  <validation-observer ref="form" tag="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('form.scopes')"
      rules="required"
      slim
    >
      <AppSelect
        v-model="formData.scopes"
        :label="$t('form.scopes')"
        :error-messages="errors"
        :items="scopeItems"
        multiple
        chips
      />
    </validation-provider>

    <validation-provider v-slot="{ errors }" :name="$t('form.expiredAt')" slim>
      <AppDatePickerInput
        v-model="formData.expireAt"
        :label="$t('form.expireAt')"
        :error-messages="errors"
        :value="formData.expireAt"
        :prepend-icon="false"
        :min-date="today"
        clearable
      />
    </validation-provider>
  </validation-observer>
</template>

<script>
import FormMixin from "@/services/mixins/FormMixin"
import AppDatePickerInput from "@/components/commons/input/AppDatePickerInput"
import AppSelect from "@/components/commons/AppSelect"
import { SCOPES } from "@/services/enums/jwtTokenEnums"
import moment from "moment/moment"

export default {
  name: "JwtTokenForm",
  components: { AppSelect, AppDatePickerInput },
  mixins: [FormMixin],
  data() {
    return {
      formData: {},
      scopeItems: SCOPES,
    }
  },
  computed: {
    today() {
      return moment().format("YYYY-MM-DD")
    },
  },
  methods: {
    async submit() {
      const isValid = await this.$refs.form.validate()
      if (!isValid) {
        return
      }

      const data = { ...this.formData }
      if (!data.expireAt) delete data.expireAt
      this.$emit("submit", data)
    },
    initData() {
      this.formData = {
        expireAt: "",
        scopes: [],
      }

      this.$refs.form?.reset()
    },
    clear() {
      this.initData()
    },
  },
  created() {
    this.initData()
  },
}
</script>
