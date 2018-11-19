import React from 'react'
import {connect} from 'react-redux';

import StateComponent from 'wrappers/StateComponent';
import TopologyMetrics from 'components/metrics/TopologyMetrics';

import * as applicationActions from 'actions/applicationActions';
import * as nodeActions from 'actions/nodeActions';
import * as metricsActions from 'actions/metricsActions';
import stateMerge from 'rootApp/utils/stateMerge';
import {stateType} from 'rootApp/types';
import ProcessChartPanel from 'components/topology/charts/ProcessChartPanel';
import ProcessTimeChartPanel from 'components/topology/charts/ProcessTimeChartPanel';
import WaitingTimeChartPanel from 'components/topology/charts/WaitingTimeChartPanel';
import QueueDepthChartPanel from 'components/topology/charts/QueueDepthChartPanel';


class TopologyNodeGraphsContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  componentDidUpdate() {
    const {needLoad, refresh} = this.props;
    needLoad && refresh();
  }

  render() {
    const {componentKey, topologyId, changeMetricsRange, changeMetricsInterval, metricsRange, altMetricsRange, interval, metricsList, nodeElements, suffix, metricsElements} = this.props;
    const nodeNames = metricsList.items.map(id => nodeElements[id].name);
    const nodeMetrics = metricsList.items.map(id => metricsElements[id + suffix].data);
    const processTimeValues = nodeMetrics.map(metrics => metrics.process_time.avg);
    const waitingTimeValues = nodeMetrics.map(metrics => metrics.waiting_time.avg);
    const queueDepthValues = nodeMetrics.map(metrics => metrics.queue_depth.max);
    return (
      <div>
        <TopologyMetrics
          topologyId={topologyId}
          componentKey={`${componentKey}.topology`}
          metricsRange={metricsRange}
          interval={interval}
          changeMetricsRange={changeMetricsRange}
          changeMetricsInterval={changeMetricsInterval}
        />
        <ProcessChartPanel requests={metricsList.data.requests} componentKey={`${componentKey}.${topologyId}.process`}/>
        <ProcessTimeChartPanel keys={nodeNames} values={processTimeValues} componentKey={`${componentKey}.${topologyId}.process-time`}/>
        <WaitingTimeChartPanel keys={nodeNames} values={waitingTimeValues} componentKey={`${componentKey}.${topologyId}.waiting-time`}/>
        <QueueDepthChartPanel keys={nodeNames} values={queueDepthValues} componentKey={`${componentKey}.${topologyId}.queue-depth`}/>
      </div>
    );
  }
}

TopologyNodeGraphsContainer.propTypes = {};

function mapStateToProps(state, ownProps){
  const {node, metrics, topology} = state;
  let metricsRange = ownProps.metricsRange;
  let suffix = `[${ownProps.interval}]` + (metricsRange ? `[${metricsRange.since}-${metricsRange.till}]` : '');
  let key = `${ownProps.topologyId}` + suffix;
  let metricsList = metrics.topologies[key];
  const needLoad = !metricsList;
  if ((!metricsList || metricsList.state === stateType.LOADING || metricsList.state === stateType.NOT_LOADED) && ownProps.altMetricsRange) {
    metricsRange = ownProps.altMetricsRange;
    suffix = `[${ownProps.interval}]` + (metricsRange ? `[${metricsRange.since}-${metricsRange.till}]` : '');
    key = `${ownProps.topologyId}` + suffix;
    metricsList = metrics.topologies[key];
  }
  const nodeList = node.lists['@topology-' + ownProps.topologyId];
  const topologyElement = topology.elements[ownProps.topologyId];
  return {
    state: stateMerge([nodeList && nodeList.state, metricsList && metricsList.state, topologyElement ? stateType.SUCCESS : stateType.LOADING]),
    metricsList,
    nodeElements: node.elements,
    suffix,
    metricsElements: metrics.elements,
    metricsRange,
    needLoad,
  };
}

function mapActionsToProps(dispatch, ownProps){
  const needNodeList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  const needMetricsList = forced => dispatch(metricsActions.needTopologyMetricsWithRequests(ownProps.topologyId, ownProps.interval, ownProps.metricsRange, forced));
  const notLoadedCallback = () => {
    needNodeList(false);
    needMetricsList(false);
  };
  return {
    notLoadedCallback,
    refresh: notLoadedCallback,
    changeMetricsRange: (since, till) => dispatch(applicationActions.setPageArgs(ownProps.pageId, {metricsRange: {since, till}})),
    changeMetricsInterval: interval => dispatch(applicationActions.setPageArgs(ownProps.pageId, {interval}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyNodeGraphsContainer));