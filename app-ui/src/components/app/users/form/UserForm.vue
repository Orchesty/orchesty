<template>
  <ValidationObserver ref="form" tag="form" :disabled="readonly" @submit.prevent="submit">
    <validation-provider v-slot="{ errors }" :name="$t('users.form.email.name')" :rules="fields.email.validations" slim>
      <app-input
        v-model="form.email"
        prepend-icon="email"
        :label="$t('users.form.email.label')"
        type="email"
        :error-messages="errors[0]"
        :readonly="readonly"
      />
    </validation-provider>
  </ValidationObserver>
</template>

<script>
import FormMixin from '../../../../services/mixins/FormMixin'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'UserForm',
  components: { AppInput },
  mixins: [FormMixin],
  props: {
    user: {
      type: Object,
      required: false,
      default: () => {},
    },
  },
  data() {
    return {
      dialog: false,
      form: {
        ...this.initForm(),
      },
      fields: {
        email: {
          id: 'email',
          validations: {
            required: true,
            email: true,
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
    initForm(user) {
      if (!user) user = {}

      return {
        email: user && user.email ? user.email : null,
      }
    },
  },
  watch: {
    user(user) {
      this.form = this.initForm(user)
    },
  },
}
</script>
