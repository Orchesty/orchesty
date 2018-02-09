import React from 'react'
import PropTypes from 'prop-types';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import {connect} from 'react-redux';
import Panel from 'rootApp/views/wrappers/Panel';
import DateRangeInput from 'rootApp/views/elements/input/DateRangeInput';
import MetricsDateRangeHeader from 'rootApp/views/components/metrics/MetricsDateRangeHeader';

class TopologyMetrics extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {metrics: {data}} = this.props;
    return (
      <div className="node-metrics tile_count">
        <div className="tile_stats_count">
          <span className="count_top">Total Processes</span>
          <div className="count">{data.process.total}</div>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Average Process Time [ms]</span>
          <div className="count">{data.process_time.avg}</div>
        </div>
      </div>
    );
  }
}

TopologyMetrics.propTypes = {};

function mapStateToProps(state, ownProps){
  const {metrics, topology} = state;
  let key = ownProps.topologyId;
  key = ownProps.interval ? `${key}[${ownProps.interval}]` : key;
  key = ownProps.metricsRange ? `${key}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : key;
  const metricsElement = metrics.topologies[key];
  return {
    state: metricsElement && metricsElement.state,
    metrics: metricsElement,
    title: topology.elements[ownProps.topologyId].name,
  }
}

export default connect(mapStateToProps)(Panel(StateComponent(TopologyMetrics),{
  HeaderComponent: MetricsDateRangeHeader
}));