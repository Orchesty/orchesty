<script>
import { Bar } from 'vue-chartjs'
export default {
  extends: Bar,
  props: {
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
    this.renderChart(this.chartData, this.options)
  },
  watch: {
    chartData(newValue, oldValue) {
      for (let i = 0; i < newValue.datasets.length; i++) {
        if (JSON.stringify(newValue.datasets[i].data) !== JSON.stringify(oldValue.datasets[i].data)) {
          this.renderChart(newValue, this.options)
        }
      }
    },
    options() {
      this.renderChart(this.chartData, this.options)
    },
  },
}
</script>
