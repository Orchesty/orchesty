import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import stateMerge from 'utils/stateMerge';
import * as nodeActions from 'actions/nodeActions';
import * as metricsActions from 'actions/metricsActions';
import StateComponent from 'wrappers/StateComponent';

import './SidebarNodeMetrics.less';
import {stateType} from 'rootApp/types';

class SidebarNodeMetrics extends React.Component {
  constructor(props, context) {
    super(props, context);
  }

  render() {
    const {metrics: {data}} = this.props;
    return (
      <div className="sidebar-node-metrics">
        <div className="metric-item">
          <span className="count_top">Total Processes:</span>
          <div className="count">{data.process.total}</div>
          <div className={'sub-count' + (data.process.errors > 0 ? ' red' : '')}><span className="count_bottom">Failed: {data.process.errors}</span></div>
        </div>
        <div className="metric-item">
          <span className="count_top">Queue Depth [msg]:</span>
          <div className="count">{data.queue_depth.avg}</div>
          <div className="sub-count"><span className="count_bottom">Max: {data.queue_depth.max}</span></div>
        </div>
        <div className="metric-item">
          <span className="count_top">Waiting Time [ms]:</span>
          <div className="count">{data.waiting_time.avg}</div>
          <div className="sub-count"><span className="count_bottom">Min: {data.waiting_time.min}</span> | <span className="count_bottom">Max: {data.waiting_time.max}</span></div>
        </div>
        <div className="metric-item">
          <span className="count_top">Process Time [ms]:</span>
          <div className="count">{data.process_time.avg}</div>
          <div className="sub-count"><span className="count_bottom">Min: {data.process_time.min}</span> | <span className="count_bottom">Max: {data.process_time.max}</span></div>
        </div>
        <div className="metric-item">
          <span className="count_top">CPU Time:</span>
          <div className="count">{data.cpu_time.avg}</div>
          <div className="sub-count"><span className="count_bottom">Min: {data.cpu_time.min}</span> | <span className="count_bottom">Max: {data.cpu_time.max}</span></div>
        </div>
        <div className="metric-item">
          <span className="count_top">Request Time [ms]:</span>
          <div className="count">{data.request_time.avg}</div>
          <div className="sub-count"><span className="count_bottom">Min: {data.request_time.min}</span> | <span className="count_bottom">Max: {data.request_time.max}</span></div>
        </div>
      </div>
    );
  }
}

SidebarNodeMetrics.propTypes = {

};

function mapStateToProps(state, ownProps){
  const {node, metrics} = state;
  if (ownProps.schemaId) {
    const searched = Object.values(node.elements).filter(node => node.topology_id=== ownProps.topologyId && node.schema_id === ownProps.schemaId);
    if (searched.length > 0) {
      const key = ownProps.metricsRange ? `${ownProps.topologyId}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : ownProps.topologyId;
      const nodeKey = ownProps.metricsRange ? `${searched[0]._id}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : ownProps.nodeId;
      const nodeList = node.lists['@topology-' + ownProps.topologyId];
      const metricsList = metrics.topologies[key];
      const metricsElement = metrics.elements[nodeKey];
      return {
        state: stateMerge([nodeList && nodeList.state, metricsList && metricsList.state, metricsElement ? stateType.SUCCESS : stateType.LOADING]),
        metrics: metricsElement
      }
    }
  }
  return {
    state: stateType.NOT_LOADED
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needNodeList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  const needMetricsList = forced => dispatch(metricsActions.needTopologyMetrics(ownProps.topologyId, ownProps.metricsRange, forced));
  return {
    needNodeList,
    needMetricsList,
    notLoadedCallback: () => {
      needNodeList(false);
      needMetricsList(false);
    }
  }
}

const SidebarNodeMetricsConnected = connect(mapStateToProps, mapActionsToProps)(StateComponent(SidebarNodeMetrics));

SidebarNodeMetrics.propTypes = {
  topologyId: PropTypes.string.isRequired,
  schemaId: PropTypes.string,
  metricsRange: PropTypes.object
};

export default SidebarNodeMetricsConnected;