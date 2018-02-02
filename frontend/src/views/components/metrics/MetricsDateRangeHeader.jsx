import React from 'react'
import PropTypes from 'prop-types';
import DateRangeInput from 'rootApp/views/elements/input/DateRangeInput';

import './MetricsDateRangeHeader.less';

class MetricsDateRangeHeader extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {metricsRange, changeMetricsRange} = this.props;
    return (
      <div className="metrics-date-range-header">
        <DateRangeInput value={metricsRange} onChange={changeMetricsRange}/>
      </div>
    );
  }
}

MetricsDateRangeHeader.propTypes = {
  metricsRange: PropTypes.object,
  changeMetricsRange: PropTypes.func.isRequired
};

export default MetricsDateRangeHeader;