import React from 'react'
import PropTypes from 'prop-types';
import DateRangeInput from 'rootApp/views/elements/input/DateRangeInput';

import './MetricsDateRangeHeader.less';
import SelectInput from 'rootApp/views/elements/input/SelectInput';
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
    const {interval, metricsRange, changeMetricsRange, changeMetricsInterval} = this.props;
    return (
      <div className="metrics-date-range-header">
        {changeMetricsInterval && <div className="metrics-interval"><SelectInput value={interval} onChange={this.changeInterval} options={intervalOptions}/></div>}
        <div className="metrics-range">
          <DateRangeInput value={metricsRange} onChange={changeMetricsRange}/>
        </div>
      </div>
    );
  }
}

MetricsDateRangeHeader.propTypes = {
  metricsRange: PropTypes.object,
  changeMetricsRange: PropTypes.func.isRequired,
  changeMetricsInterval: PropTypes.func
};

export default MetricsDateRangeHeader;