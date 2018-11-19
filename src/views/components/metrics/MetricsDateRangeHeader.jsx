import React from 'react'
import PropTypes from 'prop-types';
import DateRangeInput from 'rootApp/views/elements/input/DateRangeInput';

import './MetricsDateRangeHeader.less';
import {intervalType} from 'rootApp/types';

const intervalOptions = Object.keys(intervalType).map(key => ({value: intervalType[key].value, label: intervalType[key].caption}));

class MetricsDateRangeHeader extends React.Component {
  constructor(props) {
    super(props);
    this.changeInterval = this.changeInterval.bind(this);
  }

  changeInterval(e){
    e.preventDefault();
    this.props.changeMetricsInterval(e.target.value);
  }

  render() {
    const {interval, metricsRange, changeMetricsRange, changeMetricsInterval, last} = this.props;
    return (
      <div className={'metrics-date-range-header' + (last ? ' last' : '')}>
        <div className="metrics-range">
          <DateRangeInput value={metricsRange} onChange={changeMetricsRange}/>
        </div>
      </div>
    );
  }
}

MetricsDateRangeHeader.defaultProps = {
  last: false
};

MetricsDateRangeHeader.propTypes = {
  metricsRange: PropTypes.object,
  changeMetricsRange: PropTypes.func.isRequired,
  changeMetricsInterval: PropTypes.func,
  last: PropTypes.bool.isRequired,
};

export default MetricsDateRangeHeader;