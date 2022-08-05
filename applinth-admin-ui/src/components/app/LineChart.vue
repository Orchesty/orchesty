<template>
  <canvas id="chart" />
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { Chart, ChartData, ChartType, registerables } from "chart.js";

@Component
export default class LineChart extends Vue {
  @Prop({ type: Array, required: true })
  chartData!: Array<number>;

  @Prop({ type: Array, required: true })
  chartLabels!: Array<string>;

  createChart(chartData: object) {
    const canvas = document.getElementById("chart") as HTMLCanvasElement;
    const options = {
      type: "line" as ChartType,
      data: chartData as ChartData,
      options: {
        plugins: {
          legend: {
            display: false,
          },
        },
      },
    };
    new Chart(canvas, options);
  }

  mounted() {
    Chart.register(...registerables);
    this.createChart({
      labels: this.chartLabels,
      datasets: [
        {
          label: "My First dataset",
          backgroundColor: "rgb(255, 99, 132)",
          borderColor: "rgb(255, 99, 132)",
          data: this.chartData,
        },
      ],
    });
  }
}
</script>

<style lang="scss" scoped></style>
