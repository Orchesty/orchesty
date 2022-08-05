<template>
  <v-timeline dense reverse align-top v-if="operations.length">
    <template v-for="(operation, index) in operations">
      <v-timeline-item
        :icon="operationData(operation.status).icon"
        :small="index !== 0"
        :color="operationData(operation.status).color"
        :key="index"
        fill-dot
        :class="operationData(operation.status).class"
        :icon-color="operationData(operation.status).iconColor"
      >
        <template>
          <div class="body-1 font-weight-bold text-right">
            {{ operation.estimateTo | toLocalDateTime }}
          </div>
          <div class="body-2 font-weight-bold text-right">
            {{ operation.template.name }}
          </div>
          <div class="body-2 font-weight-bold text-right">
            {{ operation.laborer.firstname }} {{ operation.laborer.surname }}
          </div>
          <div
            v-if="isStatusTodo(operation)"
            class="body-2 font-weight-bold text-right"
          >
            {{ formattedRange(operation) }}
          </div>
        </template>
      </v-timeline-item>
    </template>
  </v-timeline>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import {
  Operation,
  OperationStatusEnum,
} from "../../types/gqlGeneratedPrivate";
import { formatRangeOfDates, toLocalDateTime } from "../../filters/datetime";

@Component({
  filters: {
    toLocalDateTime,
  },
})
export default class Timeline extends Vue {
  @Prop({ required: true, type: Array })
  operations!: [Operation];

  formattedRange(operation: Operation) {
    return formatRangeOfDates(operation.realFrom, operation.realTo);
  }

  isStatusTodo(operation: Operation) {
    return operation.status?.toUpperCase() === "TODO";
  }

  operationData(operationStatus: string) {
    switch (operationStatus) {
      case OperationStatusEnum.Done:
        return {
          icon: "check",
          color: "success",
          class: "gray--text",
          iconColor: "white",
        };
      case OperationStatusEnum.InProgress:
        return {
          icon: "mdi-progress-clock",
          color: "orange",
          class: "",
          iconColor: "white",
        };
      case OperationStatusEnum.Todo:
        return {
          icon: "mdi-help",
          color: "white",
          class: "gray--text",
          iconColor: "black",
        };
      default:
        return {
          icon: "mdi-help",
          color: "gray",
          class: "gray--text",
          iconColor: "black",
        };
    }
  }
}
</script>
