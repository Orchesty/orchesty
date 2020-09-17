import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import * as metricsActions from 'actions/metricsActions';
import StateComponent from 'wrappers/StateComponent';

import './SidebarTopologyMetrics.less';
import prettyMilliseconds from "pretty-ms";

class SidebarTopologyMetrics extends React.Component {
  constructor(props, context) {
    super(props, context);
  }

  _humanizeDuration(time) {
    if (time === "n/a") {
      return time;
    }

    return prettyMilliseconds(Number(time), {keepDecimalsOnWholeSeconds: true});
  }

  render() {
    const {metrics: {data}} = this.props;
    return (
      <div className="sidebar-topology-metrics">
        <div className="metric-item">
          <span className="count_top">Total Processes:</span>
          <div className="count">{data.process.total}</div>
          <div className={'sub-count' + (data.process.errors > 0 ? ' red' : '')}><span className="count_bottom">Failed: {data.process.errors}</span></div>
        </div>
        <div className="metric-item">
          <span className="count_top">Average Process Time</span>
          <div className="count">{this._humanizeDuration(data.process_time.avg)}</div>
          <div className="sub-count"><span className="count_bottom">Min: {this._humanizeDuration(data.process_time.min)}</span> | <span className="count_bottom">Max: {this._humanizeDuration(data.process_time.max)}</span></div>
        </div>
      </div>
    );
  }
}

SidebarTopologyMetrics.propTypes = {

};

function mapStateToProps(state, ownProps){
  const {metrics} = state;
  const key = ownProps.metricsRange ? `${ownProps.topologyId}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : ownProps.topologyId;
  const metricsElement = metrics.topologies[key];
  return {
    state: metricsElement && metricsElement.state,
    metrics: metricsElement
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needMetricsList = forced => dispatch(metricsActions.needTopologyMetrics(ownProps.topologyId, ownProps.metricsRange, forced));
  return {
    needMetricsList,
    notLoadedCallback: () => needMetricsList(false)
  }
}

const SidebarTopologyMetricsConnected = connect(mapStateToProps, mapActionsToProps)(StateComponent(SidebarTopologyMetrics));

SidebarTopologyMetricsConnected.propTypes = {
  topologyId: PropTypes.string.isRequired,
  metricsRange: PropTypes.object
};

export default SidebarTopologyMetricsConnected;