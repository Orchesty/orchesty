<template>
  <validation-observer ref="form" tag="form" @submit.prevent="submit" @keypress.enter="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('emailSender.form.host.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input v-model="form.host" autofocus :label="$t('emailSender.form.host.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('emailSender.form.port.name')"
      :rules="fields.port.validations"
      slim
    >
      <app-input v-model="form.port" :label="$t('emailSender.form.port.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('emailSender.form.username.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input v-model="form.username" :label="$t('emailSender.form.username.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('emailSender.form.password.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input
        v-model="form.password"
        input-type="password"
        :label="$t('emailSender.form.password.label')"
        :error-messages="errors"
      />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('emailSender.form.encryption.name')"
      :rules="fields.text.validations"
      slim
    >
      <v-select
        v-model="form.encryption"
        outlined
        dense
        :items="encryption"
        :label="$t('emailSender.form.encryption.label')"
        :error-messages="errors[0]"
      />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('emailSender.form.sender.name')"
      :rules="fields.emails.validations"
      slim
    >
      <app-input v-model="form.email" :label="$t('emailSender.form.sender.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('emailSender.form.receiver.name')"
      :rules="fields.emails.validations"
      slim
    >
      <v-combobox
        v-model="form.emails"
        outlined
        dense
        :label="$t('emailSender.form.receiver.label')"
        :error-messages="errors[0]"
        multiple
        chips
        append-icon=""
      />
    </validation-provider>
  </validation-observer>
</template>

<script>
import FormMixin from '@/services/mixins/FormMixin'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'EmailSender',
  components: { AppInput },
  mixins: [FormMixin],
  props: {
    service: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      encryption: ['SSL', 'TSL', 'None'],
      form: {
        ...this.initForm(),
      },
      fields: {
        text: {
          validations: {
            required: true,
          },
        },
        emails: {
          validations: {
            email: true,
            required: true,
          },
        },
        port: {
          validations: {
            required: true,
            max: 4,
            numeric: true,
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
    initForm() {
      return {
        host: this.service.settings.host || null,
        port: this.service.settings.port || null,
        username: this.service.settings.username || null,
        password: this.service.settings.password || null,
        encryption: this.service.settings.encryption || null,
        email: this.service.settings.email || null,
        emails: this.service.settings.emails || null,
      }
    },
  },
  watch: {
    service: {
      deep: true,
      handler() {
        this.form = this.initForm()
      },
    },
  },
  mounted() {
    this.$refs.form.reset()
  },
}
</script>

<style scoped></style>
