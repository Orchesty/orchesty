import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import processes from 'enums/processes';
import * as applicationActions from 'actions/applicationActions';

import StateComponent from 'wrappers/StateComponent';
import BoolValue from 'elements/BoolValue';
import SortTh from 'elements/table/SortTh';
import ActionButton from 'elements/actions/ActionButton';
import ListPagination from 'elements/table/ListPagination';
import TopologyListFilter from './TopologyListFilter';

class TopologyListTable extends React.Component {
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
    const {list: {sort}} = this.props;
    return (
      <tr>
        <SortTh name="name" state={sort} onChangeSort={this.changeSort}>Name</SortTh>
        <SortTh name="version" state={sort} onChangeSort={this.changeSort}>Version</SortTh>
        <SortTh name="descr" state={sort} onChangeSort={this.changeSort}>Description</SortTh>
        <SortTh name="visibility" state={sort} onChangeSort={this.changeSort}>Visibility</SortTh>
        <SortTh name="enabled" state={sort} onChangeSort={this.changeSort}>Enabled</SortTh>
        <th>Actions</th>
      </tr>
    );
  }

  render() {
    const {list, elements, listChangeFilter, openModal, clone, topologyDelete, publish, changeCategory, selectPage, list: {items}} = this.props;

    let rows = null;
    if (items){
      rows = items.map(id => {
          const item = elements[id];
          const menuItems = [
            {
              caption: 'Detail',
              action: () => {selectPage('topology_detail', {topologyId: id});}
            },
            {
              caption: 'Edit',
              action: () => {openModal('topology_edit', {topologyId: id});}
            }
          ];
          if (changeCategory){
            menuItems.push({
              caption: 'Change category',
             // processId: processes.topologyChangeCategory(id),
              action: () => {changeCategory(id)}
            });
          }
          if (clone){
            menuItems.push({
              caption: 'Clone',
              processId: processes.topologyClone(id),
              action: () => {clone(id)}
            });
          }
          if (publish){
            menuItems.push({
              caption: 'Publish',
              action: () => {publish(id)},
              processId: processes.topologyPublish(id),
              disabled: item.visibility == 'public'
            });
          }
          if (topologyDelete){
            const deleteDisabled = item.visibility == 'public' && item.enabled;
            menuItems.push(
              {
                caption: 'Delete',
                processId: processes.topologyDelete(id),
                action: () => {topologyDelete(id)},
                disabled: deleteDisabled,
                tooltip: deleteDisabled ? 'Disable topology first' : null
              });
          }
          return (
            <tr key={item._id}>
              <td><a href="#" onClick={e => {e.preventDefault(); selectPage('topology_detail', {topologyId: item._id});}}>{item.name}</a></td>
              <td>{item.version}</td>
              <td>{item.descr}</td>
              <td>{item.visibility}</td>
              <td><BoolValue value={item.enabled}/></td>
              <td><ActionButton item={menuItems} right={true} size="sm" /></td>
            </tr>
          )
        }
      );
    } else {
      rows = <tr>
        <td colSpan={6}>No items</td>
      </tr>;
    }

    return (
      <div className="list-table topology-list-table">
        {listChangeFilter && <TopologyListFilter filter={list.filter} changeFilter={listChangeFilter} />}
        <div className="table-wrapper">
          <table className="table table-hover">
            <thead>
            {this._renderHead()}
            </thead>
            <tbody>
              {rows}
            </tbody>
          </table>
        </div>
        <ListPagination list={list} onPageChange={this.changePage} />
      </div>
    );
  }
}

TopologyListTable.propTypes = {
  list: PropTypes.object.isRequired,
  elements: PropTypes.object.isRequired,
  openModal: PropTypes.func.isRequired,
  selectPage: PropTypes.func.isRequired,
  needList: PropTypes.func.isRequired,
  listChangeSort: PropTypes.func,
  listChangePage: PropTypes.func,
  listChangeFilter: PropTypes.func,
  clone: PropTypes.func,
  publish: PropTypes.func,
  topologyDelete: PropTypes.func,
  changeCategory: PropTypes.func
};

export default StateComponent(TopologyListTable);