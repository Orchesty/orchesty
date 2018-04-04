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
        <th className="no-wrap">Time</th>
        <th className="no-wrap">Severity</th>
        <th>Message</th>
        <th className="no-wrap">Topology name</th>
        <th className="no-wrap">Node name</th>
      </tr>
    );
  }

  _renderRows() {
    const {list, elements} = this.props;
    return list && list.items ? list.items.map(id => {
      const item = elements[id];
      return (
        <tr key={item.id}>
          <td className="no-wrap">{item.time.toLocaleString()}</td>
          <td className="no-wrap">{item.severity}</td>
          <td>{item.message}</td>
          <td className="no-wrap">{item.topology_name}</td>
          <td className="no-wrap">{item.node_name}</td>
        </tr>
      )
    }) : null;
  }
}

LogListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired
});

export default StateComponent(LogListTable);