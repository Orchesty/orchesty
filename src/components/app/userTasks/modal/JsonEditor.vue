<template>
  <v-jsoneditor v-model="JSONData" :options="options" :plus="false" :height="height" @error="onError" />
</template>

<script>
import VJsoneditor from 'v-jsoneditor'

export default {
  name: 'JsonEditor',
  components: { VJsoneditor },
  props: {
    value: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      options: {
        mode: 'tree',
        mainMenuBar: false,
      },
      height: '300px',
      JSONData: null,
    }
  },
  methods: {
    onError(err) {
      console.error(err)
    },
  },
  watch: {
    value: {
      deep: true,
      immediate: true,
      handler(newValue) {
        this.JSONData = newValue
      },
    },
    JSONData: {
      deep: true,
      handler(JSONData) {
        this.$emit('input', JSONData)
      },
    },
  },
}
</script>

<style scoped></style>
