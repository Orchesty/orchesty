import React from 'react'
import PropTypes from 'prop-types';
import ReactECharts from 'echarts-for-react';

import theme from './theme.json';

ReactECharts.propTypes.theme = PropTypes.oneOfType([PropTypes.string, PropTypes.object]);

class Chart extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {optionFn, parameters} = this.props;
    const option = optionFn(parameters);
    return <ReactECharts
      theme={theme}
      option={option}
    />;
  }
}

Chart.propTypes = {
  parameters: PropTypes.object,
  optionFn: PropTypes.func.isRequired
};

export default Chart;