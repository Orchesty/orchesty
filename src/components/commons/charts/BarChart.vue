<template>
  <canvas :id="`myChart${chartKey}`" width="400" height="150"></canvas>
</template>

<script>
import Chart from 'chart.js/auto'
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
      default: '',
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
  mounted() {
    // this.renderChart(this.chartData, this.options)

    this.ctx = document.getElementById(`myChart${this.chartKey}`).getContext('2d')
    this.chart = new Chart(this.ctx, {
      type: 'bar',
      data: this.chartData,
      options: this.options,
    })
  },
  watch: {
    chartData(newValue, oldValue) {
      for (let i = 0; i < newValue.datasets.length; i++) {
        if (JSON.stringify(newValue.datasets[i].data) !== JSON.stringify(oldValue.datasets[i].data)) {
          // this.renderChart(newValue, this.options)
          this.chart.destroy()

          this.ctx = document.getElementById(`myChart${this.chartKey}`).getContext('2d')
          this.chart = new Chart(this.ctx, {
            type: 'bar',
            data: this.chartData,
            options: this.options,
          })
        }
      }
    },
    options() {
      this.chart.destroy()
      // this.renderChart(this.chartData, this.options)
      this.ctx = document.getElementById(`myChart${this.chartKey}`).getContext('2d')
      this.chart = new Chart(this.ctx, {
        type: 'bar',
        data: this.chartData,
        options: this.options,
      })
    },
  },
}
</script>
