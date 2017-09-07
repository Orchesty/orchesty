import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as topologyActions from '../../../actions/topologyActions';
import * as applicationActions from '../../../actions/applicationActions';

import StateComponent from '../../wrappers/StateComponent';
import BoolValue from '../../elements/BoolValue';
import SortTh from '../../elements/table/SortTh';
import ActionButton from '../../elements/actions/ActionButton';
import ListPagination from '../../elements/table/ListPagination';
import TopologyNodeListTable from '../node/TopologyNodeListTable';

class TopologyListTable extends React.Component {
  constructor(props) {
    super(props);
    this.changeSort = this.changeSort.bind(this);
    this.changePage = this.changePage.bind(this);
  }

  changeSort(newSort) {
    const {topologyListChangeSort, list} = this.props;
    topologyListChangeSort(list.id, newSort);
  }
  
  changePage(newPage) {
    const {topologyListChangePage, list} = this.props;
    topologyListChangePage(list.id, newPage);
  }

  toggleSelect(id, e) {
    const {selected, setPageData} = this.props;
    e.preventDefault();
    if (selected == id){
      setPageData({selected: null});
    } else {
      setPageData({selected: id});
    }
  }

  render() {
    const {list, elements, openModal, selectPage, selected, list: {sort, items}} = this.props;

    let rows = null;
    if (items){
      rows = items.map(id => {
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
            <tr key={item._id} onClick={this.toggleSelect.bind(this, item._id)}>
              <td>{item._id}</td>
              <td>{item.status}</td>
              <td>{item.name}</td>
              <td>{item.descr}</td>
              <td><BoolValue value={item.enabled}/></td>
              <td><ActionButton item={menuItems} right={true} /></td>
            </tr>
          )
        }
      );

      if (selected){
        const index = items.indexOf(selected);
        if (index != -1){
          const sub = (
            <tr key={selected + '-sub'}>
              <td colSpan={6}>
                <TopologyNodeListTable topologyId={selected} onlyEvents />
              </td>
            </tr>
          );
          rows.splice(index + 1, 0, sub);
        }
      }
    } else {
      rows = <tr>
        <td colSpan={6}>No items</td>
      </tr>;
    }

    return (
      <div>
        <table className="table table-hover">
          <thead>
          <tr>
            <SortTh name="id" state={sort} onChangeSort={this.changeSort}>#</SortTh>
            <SortTh name="status" state={sort} onChangeSort={this.changeSort}>Status</SortTh>
            <SortTh name="name" state={sort} onChangeSort={this.changeSort}>Name</SortTh>
            <SortTh name="description" state={sort} onChangeSort={this.changeSort}>Description</SortTh>
            <SortTh name="enabled" state={sort} onChangeSort={this.changeSort}>Enabled</SortTh>
            <th>Actions</th>
          </tr>
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

TopologyListTable.propTypes = {
  list: PropTypes.object.isRequired,
  elements: PropTypes.object.isRequired,
  topologyListChangeSort: PropTypes.func.isRequired,
  topologyListChangePage: PropTypes.func.isRequired,
  openModal: PropTypes.func.isRequired,
  selectPage: PropTypes.func.isRequired,
  setPageData: PropTypes.func.isRequired
};

function mapStateToProps(state, ownProps) {
  const {topology, application: {selectedPage}} = state;
  const list = topology.lists[ownProps.listId];
  return {
    list: list,
    elements: topology.elements,
    state: list && list.state,
    selected: selectedPage.data ? selectedPage.data.selected : null
  }
}

function mapActionsToProps(dispatch){
  return {
    topologyListChangeSort: (id, sort) => dispatch(topologyActions.topologyListChangeSort(id, sort)),
    topologyListChangePage: (id, page) => dispatch(topologyActions.topologyListChangePage(id, page)),
    openModal: (id, data) => dispatch(applicationActions.openModal(id, data)),
    selectPage: (key, args) => dispatch(applicationActions.selectPage(key, args)),
    setPageData: data => dispatch(applicationActions.setPageData(data))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyListTable));