<template>
  <div>
    <textarea v-model="parsedJsonData" class="textarea"></textarea>
    <div class="parsing-error-message">
      <span v-if="isNotJson" class="font-weight-bold error--text">
        {{ $t("userTask.jsonEditor.parsingError") }}
      </span>
    </div>
  </div>
</template>

<script>
export default {
  name: "JsonEditor",
  props: {
    value: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      isNotJson: false,
      JsonData: null,
      parsedJsonData: null,
      textareaHeightGrow: 0,
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
        this.$emit("input", JsonData)
      },
    },
  },
}
</script>

<style scoped>
.parsing-error-message {
  height: 1rem;
}

.textarea {
  width: 100%;
  max-height: 80vh;
  height: 60vh;
  border: 1px solid var(--v-gray-base);
  border-radius: 4px;
  padding: 1ch;
}
</style>
