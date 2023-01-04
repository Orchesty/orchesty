<template>
  <ValidationObserver ref="form" tag="form" @submit.prevent="onSubmit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('page.status.cronTime')"
      vid="cronTime"
      :rules="fields.time.validations"
      slim
    >
      <app-input
        v-model="form.time"
        autofocus
        :label="$t('page.status.cronTime')"
        type="text"
        :error-messages="errors"
        hint="eg. */2 * * * *"
        dense
        class="mb-2"
      />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('page.status.cronParams')"
      vid="cronParams"
      :rules="fields.params.validations"
      slim
    >
      <app-input
        v-model="form.params"
        :label="$t('page.status.cronParams')"
        type="text"
        :error-messages="errors"
        hint='JSON format without {}. Eg.: "key": "val", "foo": "bar"'
        dense
        class="mb-2"
      />
    </validation-provider>
    <app-button
      :sending-title="$t('button.sending.editing')"
      :is-sending="isSending"
      :height="36"
      :button-title="$t('button.edit')"
      color="primary"
      :on-click="onSubmit"
      class="mr-2"
    />
    <app-button
      :height="36"
      :button-title="$t('button.cancel')"
      color="secondary"
      outlined
      :on-click="onCancel"
    />
  </ValidationObserver>
</template>

<script>
import AppInput from "@/components/commons/input/AppInput"
import AppButton from "@/components/commons/button/AppButton"
import cronParser from "cron-parser"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { mapActions } from "vuex"

const validateCronTime = (cronTime) => {
  try {
    cronParser.parseExpression(cronTime)
  } catch (err) {
    return {
      valid: false,
      error: err.message,
    }
  }
  return { valid: true }
}

const validateCronParams = (params) => {
  try {
    JSON.parse(`{${params}}`)
  } catch (err) {
    return {
      valid: false,
      error: err.message,
    }
  }
  return { valid: true }
}

export default {
  name: "CronPropertiesForm",
  components: { AppInput, AppButton },
  props: {
    nodeId: {
      type: String,
      required: true,
    },
    time: {
      type: String,
      required: true,
    },
    params: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      isSending: false,
      form: {
        time: "",
        params: "",
      },
      fields: {
        time: {
          id: "time",
          validations: {},
        },
        params: {
          id: "params",
          validations: {},
        },
      },
    }
  },
  watch: {
    time: {
      immediate: true,
      handler(value) {
        this.form.time = value
      },
    },
    params: {
      immediate: true,
      handler(value) {
        this.form.params = value
      },
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.NODE.UPDATE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    onCancel() {
      this.$emit("cancel")
    },
    async onSubmit() {
      let isValid = await this.$refs.form.validate()
      const resultCronTime = validateCronTime(this.form.time)
      const resultCronParams = validateCronParams(this.form.params)
      const errors = {}
      if (!resultCronTime.valid) {
        isValid = false
        errors.cronTime = [resultCronTime.error]
      }
      if (!resultCronParams.valid) {
        isValid = false
        errors.cronParams = [resultCronParams.error]
      }

      if (!isValid) {
        this.$refs.form.setErrors(errors)
        return
      }

      this.isSending = true
      await this[TOPOLOGIES.ACTIONS.NODE.UPDATE]({
        nodeId: this.nodeId,
        cron: {
          ...this.form,
        },
      })
      this.$emit("done")
      this.isSending = false
    },
  },
}
</script>
