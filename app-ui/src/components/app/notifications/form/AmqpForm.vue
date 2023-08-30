<template>
  <validation-observer ref="form" tag="form" @submit.prevent="submit" @keypress.enter="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('amqpSender.form.host.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input v-model="form.host" :label="$t('amqpSender.form.host.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('amqpSender.form.port.name')"
      :rules="fields.port.validations"
      slim
    >
      <app-input v-model="form.port" :label="$t('amqpSender.form.port.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('amqpSender.form.vhost.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input v-model="form.vhost" :label="$t('amqpSender.form.vhost.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('amqpSender.form.username.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input v-model="form.user" :label="$t('amqpSender.form.username.label')" :error-messages="errors" />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('amqpSender.form.password.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input
        v-model="form.password"
        input-type="password"
        :label="$t('amqpSender.form.password.label')"
        :error-messages="errors"
      />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('amqpSender.form.queue.name')"
      :rules="fields.text.validations"
      slim
    >
      <app-input v-model="form.queue" :label="$t('amqpSender.form.queue.label')" :error-messages="errors" />
    </validation-provider>
  </validation-observer>
</template>

<script>
import FormMixin from '@/components/commons/mixins/FormMixin'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'AmqpForm',
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
      form: {
        ...this.initForm(),
      },
      fields: {
        text: {
          validations: {
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
        vhost: this.service.settings.vhost || null,
        user: this.service.settings.user || null,
        password: this.service.settings.password || null,
        queue: this.service.settings.queue || null,
      }
    },
  },
  watch: {
    service: {
      deep: true,
      handler() {
        this.form = this.initForm(this.service.settings)
      },
    },
  },
  mounted() {
    this.$refs.form.reset()
  },
}
</script>

<style scoped></style>
