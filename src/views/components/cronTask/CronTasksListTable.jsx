import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux';
import AbstractTable from '../AbstractTable';
import StateComponent from '../../../views/wrappers/StateComponent';
import * as applicationActions from 'rootApp/actions/applicationActions';
import './CronTasksListTable.less';

class CronTasksListTable extends AbstractTable {

  _renderHead() {
    return (
      <tr>
        <th>Status</th>
        <th>Topology</th>
        <th>Node</th>
        <th>Settings</th>
      </tr>
    );
  }

  _renderRows() {
    const { list, elements, openTopology } = this.props;

    return list && list.items ? list.items.map(id => {
      const item = elements[id];

      return (
        <tr key={item.name} className={item.topology_status ? 'enabled' : 'disabled'} onClick={() => openTopology(item.topology_id)}>
          <td className="col-md-2">{item.topology_status ? 'Enabled' : 'Disabled'}</td>
          <td className="col-md-2">{item.topology}</td>
          <td className="col-md-2">{item.node}</td>
          <td className="col-md-2">{item.time}</td>
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
      <div className={event => this.getClassName(event)}>
        <div className="table-wrapper">
          <table className="table table-hover cron-task">
            <thead>{this._renderHead()}</thead>
            <tbody>{rows}</tbody>
          </table>
        </div>
      </div>
    );
  }
}

CronTasksListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired,
  cronTasks: PropTypes.arrayOf(PropTypes.shape({})).isRequired,
  initialize: PropTypes.func.isRequired,
  openTopology: PropTypes.func.isRequired,
});

const mapStateToProps = ({ cronTask: { elements, initialize } }) => ({
  cronTasks: Object.values(elements),
  initialized: initialize,
});

const mapDispatchToProps = dispatch => ({
  openTopology: topologyId => dispatch(applicationActions.openPage('topology_detail', { topologyId })),
});

export default connect(mapStateToProps, mapDispatchToProps)(StateComponent(CronTasksListTable));
