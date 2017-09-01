import React from 'react'
import {connect} from 'react-redux';

import * as topologyActions from '../../../actions/topologyActions';
import * as applicationActions from '../../../actions/applicationActions';

import SimpleState from '../../elements/state/simpleState';
import BoolValue from '../../elements/boolValue';
import SortTh from '../../elements/table/sortTh';
import ActionButton from '../../elements/actionButton';
import ListPagination from '../../elements/table/listPagination';

class TopologyListTable extends React.Component {
  constructor(props) {
    
    super(props);
    this._changeSort = this.changeSort.bind(this);
    this._changePage = this.changePage.bind(this);
  }

  changeSort(newSort) {
    const {topologyListChangeSort, list} = this.props;
    topologyListChangeSort(list.id, newSort);
  }
  
  changePage(newPage){
    const {topologyListChangePage, list} = this.props;
    topologyListChangePage(list.id, newPage);
  }

  render() {
    const {list, elements, openModal, selectPage} = this.props;
    const sort = list && list.sort;

    const rows = list && list.items ? list.items.map(id => {
        const item = elements[id];
        const menuItems = [
          {
            caption: 'Edit',
            action: () => {openModal('topology_edit', {topologyId: id});}
          },
          {
            caption: 'View schema',
            action: () => {selectPage('topology_schema', {schemaId: id});}
          }
        ];
        return (
          <tr key={item._id}>
            <td>{item._id}</td>
            <td>{item.name}</td>
            <td>{item.descr}</td>
            <td><BoolValue value={item.status}/></td>
            <td><ActionButton items={menuItems} right={true} /></td>
          </tr>
        )
      }
    ) : <tr>
      <td colSpan={5}>No items</td>
    </tr>;

    return (
      <SimpleState state={list && list.state}>
        <div>
          <table className="table table-hover">
            <thead>
            <tr>
              <SortTh name="id" state={sort} onChangeSort={this._changeSort}>#</SortTh>
              <SortTh name="name" state={sort} onChangeSort={this._changeSort}>Name</SortTh>
              <SortTh name="description" state={sort} onChangeSort={this._changeSort}>Description</SortTh>
              <SortTh name="status" state={sort} onChangeSort={this._changeSort}>Enabled</SortTh>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            {rows}
            </tbody>
          </table>
          <ListPagination list={list} onPageChange={this._changePage} />
        </div>
      </SimpleState>
    );
  }
}

function mapStateToProps(state, ownProps) {
  const {topology} = state;
  return {
    list: topology.lists[ownProps.listId],
    elements: topology.elements
  }
}


function mapActionsToProps(dispatch){
  return {
    topologyListChangeSort: (id, sort) => dispatch(topologyActions.topologyListChangeSort(id, sort)),
    topologyListChangePage: (id, page) => dispatch(topologyActions.topologyListChangePage(id, page)),
    openModal: (id, data) => dispatch(applicationActions.openModal(id, data)),
    selectPage: (key, args) => dispatch(applicationActions.selectPage(key, args))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyListTable);