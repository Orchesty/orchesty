import React from 'react'
import PropTypes from 'prop-types';
import Chart from 'elements/chart/Chart';
import {barChartOption} from 'elements/chart/chartOptions';

class ProcessTimeChart extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {keys, values} = this.props;
    const parameters = {
      keys: keys,
      values: values,
      title: 'Process time',
      subTitle: 'Time of node process from start to end',
      seriesName: 'Average process time'
    };
    return <Chart optionFn={barChartOption} parameters={parameters}/>
  }
}

ProcessTimeChart.propTypes = {
  keys: PropTypes.array.isRequired,
  values: PropTypes.array.isRequired
};

export default ProcessTimeChart;