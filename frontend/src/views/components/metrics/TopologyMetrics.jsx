import React from 'react'
import PropTypes from 'prop-types';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import {connect} from 'react-redux';
import Panel from 'rootApp/views/wrappers/Panel';
import DateRangeInput from 'rootApp/views/elements/input/DateRangeInput';
import MetricsDateRangeHeader from 'rootApp/views/components/metrics/MetricsDateRangeHeader';
import getTopologyState from 'rootApp/utils/getTopologyState';

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
          <span className="count_bottom red">Failed: {data.process.errors}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Average Process Time [ms]</span>
          <div className="count">{data.process_time.avg}</div>
          <span className="count_bottom green">Min: {data.process_time.min}</span> | <span className="count_bottom blue">Max: {data.process_time.max}</span>
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
  const topologyElement = topology.elements[ownProps.topologyId];
  const topologyState = getTopologyState(topologyElement);

  return {
    state: metricsElement && metricsElement.state,
    metrics: metricsElement,
    title: `${topologyElement.name}.v${topologyElement.version}`,
    middleHeader: <div className="middle-label"><span className={'label label-' + topologyState.label}>{topologyState.title}</span></div>
  }
}

export default connect(mapStateToProps)(Panel(StateComponent(TopologyMetrics),{
  HeaderComponent: MetricsDateRangeHeader
}));