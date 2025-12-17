<template>
  <v-alert v-if="errors.length" v-model="show" dismissible type="error">
    <span v-if="errors.length === 1"> {{ errors[0].message }} </span>
    <ul v-else>
      <li v-for="error in errors" :key="error.id">{{ error.message }}</li>
    </ul>
  </v-alert>
</template>

<script>
export default {
  name: "ErrorList",
  props: {
    errors: {
      type: Array,
      required: true,
    },
  },
  data() {
    return { show: true }
  },
}
</script>
