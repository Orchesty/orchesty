import React from 'react';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import {connect} from 'react-redux';
import Panel from 'rootApp/views/wrappers/Panel';
import MetricsDateRangeHeader from 'rootApp/views/components/metrics/MetricsDateRangeHeader';
import getTopologyState from 'rootApp/utils/getTopologyState';
import prettyMilliseconds from "pretty-ms";

class TopologyMetrics extends React.Component {
  constructor(props) {
    super(props);
  }

  _humanizeDuration(time) {
    if (time === "n/a") {
      return time;
    }

    return prettyMilliseconds(Number(time), {keepDecimalsOnWholeSeconds: true});
  }

  render() {
    const {metrics: {data}} = this.props;

	let errorColor = 'green';

	if (data.process.errors > 0) {
	  errorColor = 'red';
	}

    return (
      <div className="node-metrics tile_count">
        <div className="tile_stats_count">
          <span className="count_top">Total Processes</span>
          <div className="count">{data.process.total}</div>
          <span className={'count_bottom ' + errorColor}>Failed: {data.process.errors}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Average Process Time</span>
          <div className="count">{this._humanizeDuration(data.process_time.avg)}</div>
          <span className="count_bottom blue">Min: {this._humanizeDuration(data.process_time.min)}</span> | <span className="count_bottom blue">Max: {this._humanizeDuration(data.process_time.max)}</span>
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