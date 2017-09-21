import React from 'react';
import PropTypes from 'prop-types';

import ListPagination from 'elements/table/ListPagination';
import SortTh from 'elements/table/SortTh';
import StateComponent from 'wrappers/StateComponent';
import BoolValue from 'elements/BoolValue';
import ActionButton from 'elements/actions/ActionButton';

class NodeListTable extends React.Component {
  constructor(props) {
    super(props);
    this._checkList(props);
  }

  _checkList(props){
    if (!props.list){
      props.needList();
    }
  }

  componentWillReceiveProps(nextProps){
    this._checkList(nextProps);
  }

  _renderHead(){
    const {listChangeSort, withTopology, list: {sort}} = this.props;
    return (
      <tr>
        <SortTh name="id" state={sort} onChangeSort={listChangeSort}>#</SortTh>
        {withTopology && <th>Topology</th>}
        <SortTh name="name" state={sort} onChangeSort={listChangeSort}>Name</SortTh>
        <th>Type</th>
        <th>Handler</th>
        <th>Service</th>
        <SortTh name="enabled" state={sort} onChangeSort={listChangeSort}>Enabled</SortTh>
        <th>Actions</th>
      </tr>
    );
  }

  render() {
    const {list, elements, withTopology, updateNode, runNode, onlyEvents, listChangePage} = this.props;
    const rows = list && list.items ? list.items.map(id => {
      const item = elements[id];
      if (!onlyEvents || item.handler == 'event') {
        const menuItems = item.handler == 'event' ? [
          {
            caption: 'Run',
            action: () => {
              runNode(item._id);
            }
          },
          {
            caption: item.enabled ? 'Disable' : 'Enable',
            action: () => {
              updateNode(item._id, {enable: !item.enabled})
            }
          }
        ] : null;

        return (
          <tr key={item._id}>
            <td>{item._id}</td>
            {withTopology && <td>{item.topology_id}</td>}
            <td>{item.name}</td>
            <td>{item.type}</td>
            <td>{item.handler}</td>
            <td>{item.service}</td>
            <td><BoolValue value={item.enabled}/></td>
            <td><ActionButton item={menuItems} right={true}/></td>
          </tr>
        )
      } else {
        return undefined;
      }
    }) : <tr>
      <td colSpan={6}>No items</td>
    </tr>;
    return (
      <div className="node-list-table">
        <table className="table table-hover">
          <thead>
            {this._renderHead()}
          </thead>
          <tbody>
            {rows}
          </tbody>
        </table>
        <ListPagination list={list} onPageChange={listChangePage} />
      </div>
    );
  }
}

NodeListTable.defaultProps = {
  withTopology: true,
  onlyEvents: false
};

NodeListTable.propTypes = {
  list: PropTypes.object,
  elements: PropTypes.object.isRequired,
  withTopology: PropTypes.bool.isRequired,
  onlyEvents: PropTypes.bool.isRequired,
  needList: PropTypes.func.isRequired,
  listChangeSort: PropTypes.func,
  listChangePage: PropTypes.func,
  updateNode: PropTypes.func,
  runNode: PropTypes.func
};

export default StateComponent(NodeListTable);