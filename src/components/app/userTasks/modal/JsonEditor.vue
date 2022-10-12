<template>
  <div>
    <v-textarea v-model="parsedJsonData" :height="height" full-width />
    <div class="parsing-error-message">
      <span v-if="isNotJson" class="font-weight-bold error--text">
        {{ $t('userTask.jsonEditor.parsingError') }}
      </span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'JsonEditor',
  props: {
    value: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      isNotJson: false,
      height: '300',
      JsonData: null,
      parsedJsonData: null,
    }
  },
  watch: {
    parsedJsonData(newValue) {
      try {
        this.JsonData = JSON.parse(newValue)
        this.isNotJson = false
      } catch (e) {
        this.isNotJson = true
      }
    },
    value: {
      deep: true,
      immediate: true,
      handler(newValue) {
        this.isNotJson = false
        this.parsedJsonData = JSON.stringify(newValue, null, 4)
      },
    },
    JsonData: {
      deep: true,
      handler(JsonData) {
        this.$emit('input', JsonData)
      },
    },
  },
}
</script>

<style scoped>
.parsing-error-message {
  height: 1rem;
}
</style>
