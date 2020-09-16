import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as topologyActions from "../../../actions/topologyActions";

class TopologyNodeProcessesContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    this.props.load();
  }

  _mapStatuses(status){
    switch (status){
      case "OK":
        return "Success";
      case "NOK":
        return "Error";
      case "IP":
        return "Running";
      default:
        return "Unknown";
    }
  }

  _renderHead() {
    return (
      <tr>
        <th className="col-md-2">CorrelationId</th>
        <th className="col-md-2">Started</th>
        <th className="col-md-2">Finished</th>
        <th className="col-md-2">Duration (ms)</th>
        <th className="col-md-1">Status</th>
        <th className="col-md-1">Progress</th>
        <th className="col-md-2">Nodes</th>
      </tr>
    );
  }

  _renderRows() {
    const {items} = this.props;

    return items && !items.empty ? items.map(item => {
      console.log(item);
      return (
        <tr key={item.correlationId}>
          <td className="col-md-2">{item.correlationId}</td>
          <td className="col-md-2">{item.started}</td>
          <td className="col-md-2">{item.finished}</td>
          <td className="col-md-2">{item.duration}</td>
          <td className="col-md-1">{this._mapStatuses(item.status)}</td>
          <td className="col-md-1">{item.nodesProcessed}/{item.nodesTotal}</td>
          <td className="col-md-2">
            {item.nodes.map((node) => {
              return (<p key={node.processId}>{`${node.name} - ${this._mapStatuses(node.status)}`}</p>)
            })}
          </td>
        </tr>
      )
    }) : null;
  }

  render() {
    let rows = this._renderRows();

    if (!rows) {
      rows = <tr>
        <td colSpan={4}>No items</td>
      </tr>;
    }

    return (
      <div className="x_panel">
        <div className="x_title">
          <h2>Latest processes</h2>
        </div>
        <div className="table-wrapper">
          <table className="table table-hover">
            <thead>{this._renderHead()}</thead>
            <tbody>{rows}</tbody>
          </table>
        </div>
      </div>
    );
  }
}

TopologyNodeProcessesContainer.propTypes = {
  topologyId: PropTypes.string.isRequired,
  pageId: PropTypes.string.isRequired,
  componentKey: PropTypes.string.isRequired,
  items: PropTypes.array.isRequired
};

function mapStateToProps(state) {
  return {
    items: state.processesList.elements,
  }
}

function mapDispatchToProps(dispatch, ownProps) {
  return {
    load: () => dispatch(topologyActions.loadProcesses(ownProps.topologyId))
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(TopologyNodeProcessesContainer);
