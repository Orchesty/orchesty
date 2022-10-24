<template>
  <canvas :id="`chartWithKey${chartKey}`" width="400" height="150"></canvas>
</template>

<script>
import Chart from "chart.js/auto"
export default {
  data() {
    return {
      ctx: null,
      chart: null,
    }
  },
  props: {
    chartKey: {
      type: String,
      default: "",
    },
    chartData: {
      type: Object,
      default: null,
    },
    options: {
      type: Object,
      default: null,
    },
  },
  methods: {
    canvasDraw() {
      this.ctx = document
        .getElementById(`chartWithKey${this.chartKey}`)
        .getContext("2d")
      this.chart = new Chart(this.ctx, {
        type: "bar",
        data: this.chartData,
        options: this.options,
      })
    },
    canvasReDraw() {
      this.chart.destroy()
      this.canvasDraw()
    },
  },
  mounted() {
    this.canvasDraw()
  },
  watch: {
    chartData(newValue, oldValue) {
      for (let i = 0; i < newValue.datasets.length; i++) {
        if (
          JSON.stringify(newValue.datasets[i].data) !==
          JSON.stringify(oldValue.datasets[i].data)
        ) {
          this.canvasReDraw()
        }
      }
    },
    options() {
      this.canvasDraw()
    },
  },
}
</script>
