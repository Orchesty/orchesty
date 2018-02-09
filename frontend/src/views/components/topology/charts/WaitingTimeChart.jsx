import React from 'react'
import PropTypes from 'prop-types';
import Chart from 'elements/chart/Chart';
import {barChartOption} from 'elements/chart/chartOptions';

class WaitingTimeChart extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {keys, values} = this.props;
    const parameters = {
      keys: keys,
      values: values,
      title: 'Waiting time',
      subTitle: 'Waiting time of document in queue',
      seriesName: 'Average waiting time'
    };
    return <Chart optionFn={barChartOption} parameters={parameters}/>
  }
}

WaitingTimeChart.propTypes = {
  keys: PropTypes.array.isRequired,
  values: PropTypes.array.isRequired
};

export default WaitingTimeChart;