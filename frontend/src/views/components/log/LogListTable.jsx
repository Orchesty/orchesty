import React from 'react'
import PropTypes from 'prop-types';
import AbstractTable from 'components/AbstractTable';
import StateComponent from 'wrappers/StateComponent';

import './LogListTable.less';

class LogListTable extends AbstractTable {
  constructor(props) {
    super(props);
  }

  getClassName(){
    return super.getClassName() + ' log-list-table';
  }

  _renderHead(){
    return (
      <tr>
        <th className="time-col">Time</th>
        <th className="type-col">Type</th>
        <th>Message</th>
        <th className="topology-col">Topology name</th>
        <th className="node-col">Node name</th>
      </tr>
    );
  }

  _renderRows() {
    const {list, elements} = this.props;
    return list && list.items ? list.items.map(id => {
      const item = elements[id];
      return (
        <tr key={item.id}>
          <td>{item.time.toLocaleString()}</td>
          <td>{item.type}</td>
          <td>{item.message}</td>
          <td>{item.topology_name}</td>
          <td>{item.node_name}</td>
        </tr>
      )
    }) : null;
  }
}

LogListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired
});

export default StateComponent(LogListTable);