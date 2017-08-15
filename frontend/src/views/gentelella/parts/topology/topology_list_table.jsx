import React from 'react';
import Flusanec from 'flusanec';
import FlusanecMenu from 'flusanec/src/view_models/menu';

import BoolValue from '../../components/values/bool_value';
import SimpleState from '../../components/simple_state/simple_state';
import SortTh from '../../components/table/sort_th';
import ActionTr from '../../components/table/action_tr';
import ListPagination from '../../components/pagination/list_pagination';

class TopologyListTable extends Flusanec.Component {
  _initialize() {
    this._onDataChange = this.onDataChange.bind(this);
    this._onStateChange = this.onStateChange.bind(this);
    this._onParamsChange = this.onParamsChange.bind(this);
    this._onSortClick = this.onSortClick.bind(this);
  }

  _useProps(props) {
    this.topologyList = props.topologyList;
  }

  _finalization() {
    this.topologyList = null;
  }

  _generateMenuItems():Array<MenuItem> {
    let items = [];
    this._topologyList.canRefresh() && items.push(
      new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Refresh', null, () => {
        this._topologyList.refresh()
      })
    );
    return items;
  }

  set topologyList(topologyList:SortPersistentList) {
    if (this._topologyList != topologyList) {
      this._topologyList && this._topologyList.removeDataChangeListener(this._onDataChange);
      this._topologyList && this._topologyList.removeStateChangeListener(this._onStateChange);
      this._topologyList && this._topologyList.removeSortChangeListener(this._onParamsChange);
      this._topologyList = topologyList;
      this._topologyList && this._topologyList.addStateChangeListener(this._onStateChange);
      this._topologyList && this._topologyList.addDataChangeListener(this._onDataChange);
      this._topologyList && this._topologyList.addSortChangeListener(this._onParamsChange);
    }
  }

  onDataChange() {
    this.forceUpdate();
  }

  onStateChange() {
    this.forceUpdate();
  }

  onParamsChange() {
    this.forceUpdate();
  }

  onSortClick(newSort) {
    this._topologyList.sort = newSort;
  }

  mainAction(item) {
    this.props.contextServices.controller.topologyEditAction(item.id);
  }

  viewSchemeAction(item){
    this.props.contextServices.controller.topologySchemeAction(item.id);
  }

  disableAction(item) {
    this.props.disable(item);
  }

  enableAction(item) {
    this.props.enable(item);
  }

  makeMenuItem(item) {
    return new FlusanecMenu.Menu([
      new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Edit', null, () => {this.mainAction(item)}),
      new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'View scheme', null, () => {this.viewSchemeAction(item)}),
      item.enabled ?
        new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Disable', null, () => {this.disableAction(item)}):
        new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Enable', null, () => {this.enableAction(item)})
    ]);
  }


  render() {
    let rows = this._topologyList.items ? this._topologyList.items.map(item =>
      <ActionTr key={item.identity}
        contextServices={this.props.contextServices}
        menu={this.makeMenuItem(item)}
        mainAction={this.mainAction.bind(this, item)}
      >
        <td>{item.id}</td>
        <td>{item.name}</td>
        <td>{item.description}</td>
        <td><BoolValue value={item.enabled}/></td>
      </ActionTr>
    ) : [];

    const sort = this._topologyList.sort;

    return (
      <SimpleState state={this._topologyList.state}>
        <div>
          <table className="table table-hover">
            <thead>
            <tr>
              <SortTh name="id" state={sort} onSortClick={this._onSortClick}>#</SortTh>
              <SortTh name="name" state={sort} onSortClick={this._onSortClick}>Name</SortTh>
              <SortTh name="description" state={sort} onSortClick={this._onSortClick}>Description</SortTh>
              <SortTh name="enabled" state={sort} onSortClick={this._onSortClick}>Enabled</SortTh>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            {rows}
            </tbody>
          </table>
          <ListPagination list={this._topologyList} pageItemCount={this.props.pageItemCount}/>
        </div>
      </SimpleState>
    );
  }
}

export default TopologyListTable;