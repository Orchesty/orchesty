import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as nodeActions from 'actions/nodeActions';
import * as metricsActions from 'actions/metricsActions';
import stateMerge from 'utils/stateMerge';

import StateComponent from 'wrappers/StateComponent';
import NodeMetrics from 'components/metrics/NodeMetrics';
import {needTopology} from 'rootApp/actions/topologyActions';
import {stateType} from 'rootApp/types';
import TopologyMetrics from 'components/metrics/TopologyMetrics';
import * as applicationActions from 'rootApp/actions/applicationActions';


class TopologyNodeMetricsContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {topologies, nodes, nodeList, componentKey, topologyId, changeMetricsRange, metricsRange} = this.props;
    const nodeItems = nodeList.items.map(nodeId => (
      <NodeMetrics
        key={nodeId}
        nodeId={nodeId}
        nodeName={nodes[nodeId].name}
        nodeType={nodes[nodeId].type}
        topologyId={topologies[topologyId]._id}
        topologyName={topologies[topologyId].name}
        componentKey={`${componentKey}.${nodeId}`}
        metricsRange={metricsRange}
      />
      ));
    return (
      <div>
        <TopologyMetrics
          topologyId={topologyId}
          componentKey={`${componentKey}.topology`}
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
  topologies: PropTypes.object.isRequired,
  nodes: PropTypes.object.isRequired,
  changeMetricsRange: PropTypes.func.isRequired,
};

function mapStateToProps(state, ownProps){
  const {node, metrics, topology} = state;
  let metricsRange = ownProps.metricsRange;
  let key = metricsRange ? `${ownProps.topologyId}[${metricsRange.since}-${metricsRange.till}]` : ownProps.topologyId;
  let metricsList = metrics.topologies[key];
  if ((!metricsList || metricsList.state === stateType.LOADING || metricsList.state === stateType.NOT_LOADED) && ownProps.altMetricsRange) {
    metricsRange = ownProps.altMetricsRange;
    key = `${ownProps.topologyId}[${metricsRange.since}-${metricsRange.till}]`;
    metricsList = metrics.topologies[key];
  }

  const topologies = topology.elements;
  const nodes = node.elements;
  const nodeList = node.lists['@topology-' + ownProps.topologyId];
  const topologyElement = topology.elements[ownProps.topologyId];
  return {
    state: stateMerge([nodeList && nodeList.state, metricsList && metricsList.state, topologyElement ? stateType.SUCCESS : stateType.LOADING]),
    topologies,
    nodes,
    nodeList,
    metricsRange
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
    changeMetricsRange: (since, till) => dispatch(applicationActions.setPageArgs(ownProps.pageId, {metricsRange: {since, till}}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyNodeMetricsContainer));
