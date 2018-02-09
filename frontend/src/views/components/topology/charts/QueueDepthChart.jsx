import React from 'react'
import PropTypes from 'prop-types';
import Chart from 'elements/chart/Chart';
import {barChartOption} from 'elements/chart/chartOptions';

class QueueDepthChart extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {keys, values} = this.props;
    const parameters = {
      keys: keys,
      values: values,
      title: 'Queue depth',
      subTitle: 'Number documents in queue',
      seriesName: 'Max queue depth'
    };
    return <Chart optionFn={barChartOption} parameters={parameters}/>
  }
}

QueueDepthChart.propTypes = {
  keys: PropTypes.array.isRequired,
  values: PropTypes.array.isRequired
};

export default QueueDepthChart;