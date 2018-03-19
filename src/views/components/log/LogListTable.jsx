import React from 'react'
import PropTypes from 'prop-types';
import AbstractTable from 'components/AbstractTable';
import StateComponent from 'wrappers/StateComponent';

class LogListTable extends AbstractTable {
  constructor(props) {
    super(props);
  }

  _renderHead(){
    return (
      <tr>
        <th>Time</th>
        <th>Type</th>
        <th>Message</th>
        <th>Topology name</th>
        <th>Node name</th>
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