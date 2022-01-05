<template>
  <div>
    <v-btn :title="$t('button.back')" icon @click="routeBack">
      <v-icon>mdi-arrow-left-circle-outline</v-icon>
    </v-btn>
    <v-btn
      icon
      class="ml-1"
      :title="$t('button.save')"
      @click="
        () => {
          saveDiagram()
        }
      "
    >
      <v-icon>mdi-content-save-outline</v-icon>
    </v-btn>
    <v-menu offset-y>
      <template #activator="{ on, attrs }">
        <v-btn icon text :disabled="isSending" class="ml-1" v-bind="attrs" v-on="on">
          <v-icon>mdi-dots-vertical</v-icon>
        </v-btn>
      </template>
      <v-list dense>
        <v-list-item link @click="$refs.import.click()">
          <input
            id="import"
            ref="import"
            type="file"
            hidden
            @change="
              (e) => {
                setFileInput(e)
              }
            "
          />
          <v-list-item-title>{{ $t('topologies.editor.actions.import') }}</v-list-item-title>
        </v-list-item>
        <v-list-item link @click="exportDiagram()">
          <v-list-item-title>{{ $t('topologies.editor.actions.export') }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>

<script>
export default {
  name: 'ActionButtons',
  props: {
    saveDiagram: {
      type: Function,
      required: true,
    },
    setFileInput: {
      type: Function,
      required: true,
    },
    exportDiagram: {
      type: Function,
      required: true,
    },
    routeBack: {
      type: Function,
      required: true,
    },
    isSending: {
      type: Boolean,
      required: true,
    },
  },
}
</script>

<style scoped></style>
