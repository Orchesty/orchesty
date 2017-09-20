import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as applicationActions from '../../../actions/applicationActions';

import StateComponent from '../../wrappers/StateComponent';
import BoolValue from '../../elements/BoolValue';
import SortTh from '../../elements/table/SortTh';
import ActionButtonPanel from '../../elements/actions/ActionButtonPanel';
import ListPagination from '../../elements/table/ListPagination';
import TopologyNodeListTable from '../node/TopologyNodeListTable';

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

  toggleSelect(id, e) {
    const {selected, setPageData} = this.props;
    e.preventDefault();
    if (selected == id){
      setPageData({selected: null});
    } else {
      setPageData({selected: id});
    }
  }

  _renderHead(){
    const {list: {sort}} = this.props;
    return (
      <tr>
        <SortTh name="id" state={sort} onChangeSort={this.changeSort}>#</SortTh>
        <SortTh name="status" state={sort} onChangeSort={this.changeSort}>Status</SortTh>
        <SortTh name="name" state={sort} onChangeSort={this.changeSort}>Name</SortTh>
        <SortTh name="version" state={sort} onChangeSort={this.changeSort}>Version</SortTh>
        <SortTh name="description" state={sort} onChangeSort={this.changeSort}>Description</SortTh>
        <SortTh name="enabled" state={sort} onChangeSort={this.changeSort}>Enabled</SortTh>
        <th>Actions</th>
      </tr>
    );
  }

  render() {
    const {list, elements, openModal, clone, selectPage, selected, list: {items}} = this.props;

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
            },
            {
              caption: 'View schema',
              action: () => {selectPage('topology_schema', {schemaId: id});}
            },
            {
              caption: 'Clone',
              action: () => {clone(id)}
            }
          ];
          return (
            <tr key={item._id} onClick={this.toggleSelect.bind(this, item._id)}>
              <td>{item._id}</td>
              <td>{item.status}</td>
              <td>{item.name}</td>
              <td>{item.version}</td>
              <td>{item.descr}</td>
              <td><BoolValue value={item.enabled}/></td>
              <td><ActionButtonPanel items={menuItems} right={true} size="sm" /></td>
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
      <div className="topology-list-table">
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

TopologyListTable.propTypes = {
  list: PropTypes.object.isRequired,
  elements: PropTypes.object.isRequired,
  openModal: PropTypes.func.isRequired,
  selectPage: PropTypes.func.isRequired,
  setPageData: PropTypes.func.isRequired,
  needList: PropTypes.func.isRequired,
  selected: PropTypes.string,
  listChangeSort: PropTypes.func,
  listChangePage: PropTypes.func,
  clone: PropTypes.func,
};

function mapStateToProps(state, ownProps) {
  const {application: {selectedPage}} = state;
  return {
    selected: selectedPage.data ? selectedPage.data.selected : null
  }
}

function mapActionsToProps(dispatch){
  return {
    setPageData: data => dispatch(applicationActions.setPageData(data)),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyListTable));