import React from 'react';
import PropTypes from 'prop-types';
import Panel from 'rootApp/views/wrappers/Panel';
import {connect} from 'react-redux';
import prettyMilliseconds from "pretty-ms";

import './NodeMetrics.less';
import {menuItemType} from 'rootApp/types';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import * as applicationActions from 'rootApp/actions/applicationActions';
import * as nodeActions from 'rootApp/actions/nodeActions';

class NodeMetrics extends React.Component {
  constructor(props) {
    super(props);
  }

  UNSAFE_componentWillMount() {
    this.sendActions(this.props);
  }

  UNSAFE_componentWillReceiveProps(props) {
    if (props.tests !== this.props.tests || props.node !== this.props.node) {
      this.sendActions(props);
    }
  }

  sendActions(props) {
    const {node, setActions, metrics, tests, topology, runNode, updateNode} = props;
    const actions = [];
    if (metrics.data.process.errors) {
      actions.push({
        type: menuItemType.BADGE,
        icon: 'bg-red white',
        caption: metrics.data.process.errors
      });
    }
    const test = tests[node._id];
    if (test) {
      actions.push({
        type: menuItemType.TEXT,
        icon: test.code === 200 ? 'fa fa-check green' : 'fa fa-warning orange',
        caption: test.message
      });
    }
    if (node.handler === 'event') {
      if (topology.enabled) {
        actions.push({
          type: menuItemType.ACTION,
          icon: 'fa fa-play',
          caption: 'Run',
          action: runNode
        });
      }
      actions.push({
        type: menuItemType.ACTION,
        icon: 'fa fa-power-off' + (node.enabled ? ' green' : ''),
        caption: node.enabled ? 'Disable' : 'Enable',
        action: () => {
          updateNode({enabled: !node.enabled})
        }
      });
    }

    setActions(actions);
  }

  _humanizeDuration(time) {
    if (time === "n/a") {
      return time;
    }

    return prettyMilliseconds(Number(time), {keepDecimalsOnWholeSeconds: true});
  }

  render() {
    const {metrics: {data}, nodeType} = this.props;

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
          <span className="count_top">Queue Depth [msg]</span>
          <div className="count">{data.queue_depth.avg}</div>
          <span className="count_bottom blue">Max: {data.queue_depth.max}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Waiting Time</span>
          <div className="count">{this._humanizeDuration(data.waiting_time.avg)}</div>
          <span className="count_bottom blue">Min: {this._humanizeDuration(data.waiting_time.min)}</span> | <span
          className="count_bottom blue">Max: {this._humanizeDuration(data.waiting_time.max)}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Process Time</span>
          <div className="count">{this._humanizeDuration(data.process_time.avg)}</div>
          <span className="count_bottom blue">Min: {this._humanizeDuration(data.process_time.min)}</span> | <span
          className="count_bottom blue">Max: {this._humanizeDuration(data.process_time.max)}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">CPU Time</span>
          <div className="count">{data.cpu_time.avg}</div>
          <span className="count_bottom blue">Min: {data.cpu_time.min}</span> | <span
          className="count_bottom blue">Max: {data.cpu_time.max}</span>
        </div>

        <div className="tile_stats_count">
          {(nodeType === "connector" || data.request_time.avg !== "n/a") &&
          <div>
            <span className="count_top">Request Time</span>
            <div className="count">{this._humanizeDuration(data.request_time.avg)}</div>
            <span className="count_bottom blue">Min: {this._humanizeDuration(data.request_time.min)}</span> | <span
            className="count_bottom blue">Max: {this._humanizeDuration(data.request_time.max)}</span>
          </div>
          }
        </div>

      </div>
    );
  }
}

NodeMetrics.propTypes = {
  setActions: PropTypes.func.isRequired,
  metrics: PropTypes.object.isRequired,
  tests: PropTypes.object,
  topology: PropTypes.object.isRequired,
  runNode: PropTypes.func,
  updateNode: PropTypes.func
};

function mapStateToProps(state, ownProps) {
  const {node, metrics, topology} = state;
  const key = ownProps.metricsRange ? `${ownProps.nodeId}[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : ownProps.nodeId;
  const nodeElement = node.elements[ownProps.nodeId];
  const metricsElement = metrics.elements[key];
  return {
    state: metricsElement && metricsElement.state,
    node: nodeElement,
    metrics: metricsElement,
    title: nodeElement.name,
    subTitle: `${nodeElement.type} ${nodeElement.handler}`,
    tests: node.tests,
    topology: topology.elements[nodeElement.topology_id]
  }
}

function mapActionsToProps(dispatch, {nodeId, nodeName, nodeType, topologyId, topologyName}) {
  return {
    updateNode: data => dispatch(nodeActions.nodeUpdate(nodeId, data)),
    runNode: () => dispatch(applicationActions.openModal('node_run', {
      nodeId,
      nodeName,
      nodeType,
      topologyId,
      topologyName
    })),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(Panel(StateComponent(NodeMetrics)));