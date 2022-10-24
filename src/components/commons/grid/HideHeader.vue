<template>
  <v-menu
    bottom
    left
    :close-on-content-click="false"
    max-height="250px"
    allow-overflow
  >
    <template #activator="{ on }">
      <v-btn icon v-on="on">
        <v-icon>tune</v-icon>
      </v-btn>
    </template>

    <v-list>
      <v-list-item v-for="header in innerHeaders" :key="header.value">
        <v-list-item-action>
          <v-switch
            v-model="header.visible"
            dense
            :disabled="header.alwaysVisible === true"
            @change="onChange"
          />
        </v-list-item-action>
        <v-list-item-title>{{ $t(header.text) }}</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script>
export default {
  name: "HideHeader",
  props: {
    headers: {
      type: Array,
      required: true,
    },
    onColumnsChange: {
      type: Function,
      required: true,
    },
  },
  data() {
    return {
      innerHeaders: JSON.parse(
        JSON.stringify(
          this.headers.map((item) => {
            if (item.alwaysVisible === true) {
              item.visible = true
            }

            return item
          })
        )
      ),
    }
  },
  methods: {
    onChange() {
      this.onColumnsChange(this.innerHeaders)
    },
  },
}
</script>
