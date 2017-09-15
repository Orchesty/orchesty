import React from 'react';
import PropTypes from 'prop-types';

import ListPagination from '../../elements/table/ListPagination';
import SortTh from '../../elements/table/SortTh';
import StateComponent from '../../wrappers/StateComponent';
import BoolValue from '../../elements/BoolValue';
import ActionButton from '../../elements/actions/ActionButton';

class NodeListTable extends React.Component {
  constructor(props) {
    super(props);
    this.changeSort = this.changeSort.bind(this);
    this.changePage = this.changePage.bind(this);
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

  changeSort(newSort) {
    this.props.listChangeSort(newSort);
  }

  changePage(newPage) {
    this.props.listChangePage(newPage);
  }

  _renderHead(){
    const {listChangeSort, withTopology, list: {sort}} = this.props;
    if (listChangeSort) {
      return (
        <tr>
          <SortTh name="id" state={sort} onChangeSort={this.changeSort}>#</SortTh>
          {withTopology && <th>Topology</th>}
          <th>Type</th>
          <SortTh name="name" state={sort} onChangeSort={this.changeSort}>Name</SortTh>
          <th>Service</th>
          <SortTh name="enabled" state={sort} onChangeSort={this.changeSort}>Enabled</SortTh>
          <th>Actions</th>
        </tr>
      );
    } else {
      return (
        <tr>
          <th>#</th>
          {withTopology && <th>Topology</th>}
          <th>Type</th>
          <th>Name</th>
          <th>Service</th>
          <th>Enabled</th>
          <th>Actions</th>
        </tr>
      );
    }
  }

  render() {
    const {list, elements, withTopology, updateNode, runNode, onlyEvents} = this.props;
    const rows = list && list.items ? list.items.map(id => {
      const item = elements[id];
      if (!onlyEvents || item.type == 'event') {
        const menuItems = item.type == 'event' ? [
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
            <td>{item.type}</td>
            <td>{item.name}</td>
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
        <ListPagination list={list} onPageChange={this.changePage} />
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