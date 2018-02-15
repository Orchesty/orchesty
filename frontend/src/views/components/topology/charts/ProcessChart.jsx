import React from 'react'
import PropTypes from 'prop-types';
import Chart from 'elements/chart/Chart';
import {lineTimeChartOption} from 'elements/chart/chartOptions';

class ProcessChart extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {requests} = this.props;
    const parameters = {
      data: requests,
      title: 'Process',
      subTitle: 'Number of processes in time',
      seriesName: 'processes'
    };
    return <Chart optionFn={lineTimeChartOption} parameters={parameters}/>
  }
}

ProcessChart.propTypes = {
  requests: PropTypes.oneOfType([PropTypes.object, PropTypes.array]).isRequired
};

export default ProcessChart;