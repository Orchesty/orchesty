import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as nodeActions from 'actions/nodeActions';
import * as metricsActions from 'actions/metricsActions';
import stateMerge from 'utils/stateMerge';

import StateComponent from 'wrappers/StateComponent';
import NodeMetrics from 'components/metrics/NodeMetrics';
import * as topologyActions from 'rootApp/actions/topologyActions';
import {needTopology} from 'rootApp/actions/topologyActions';
import {stateType} from 'rootApp/types';
import TopologyMetrics from 'components/metrics/TopologyMetrics';
import * as applicationActions from 'rootApp/actions/applicationActions';


class TopologyNodeMetricsContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {nodeList, componentKey, topologyId, changeMetricsRange, metricsRange} = this.props;
    const nodeItems = nodeList.items.map(nodeId => <NodeMetrics key={nodeId} nodeId={nodeId} componentKey={`${componentKey}.${nodeId}`} metricsRange={metricsRange} />);
    return (
      <div>
        <TopologyMetrics
          topologyId={topologyId}
          componentKey={`${componentKey}.${topologyId}`}
          metricsRange={metricsRange}
          changeMetricsRange={changeMetricsRange}
        />
        {nodeItems}
      </div>
    );
  }
}

TopologyNodeMetricsContainer.propTypes = {
  componentKey: PropTypes.string.isRequired,
  nodeList: PropTypes.object.isRequired,
  changeMetricsRange: PropTypes.func.isRequired,
};

function mapStateToProps(state, ownProps){
  const {node, metrics, topology} = state;
  const key = ownProps.metricsRange ? `${ownProps.topologyId}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : ownProps.topologyId;
  const nodeList = node.lists['@topology-' + ownProps.topologyId];
  const metricsList = metrics.topologies[key];
  const topologyElement = topology.elements[ownProps.topologyId];
  return {
    state: stateMerge([nodeList && nodeList.state, metricsList && metricsList.state, topologyElement ? stateType.SUCCESS : stateType.LOADING]),
    nodeList
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
    },
    changeMetricsRange: (since, till) => dispatch(ownProps.setPageArgs({metricsRange: {since, till}}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyNodeMetricsContainer));