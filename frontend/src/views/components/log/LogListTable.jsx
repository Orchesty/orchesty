import React from 'react';
import PropTypes from 'prop-types';
import moment from 'moment';
import AbstractTable from 'components/AbstractTable';
import StateComponent from 'wrappers/StateComponent';
import SortTh from 'elements/table/SortTh';
import './LogListTable.less';
import ListPagination from 'elements/table/ListPagination';
import LogListFilter from 'components/log/LogListFilter';

class LogListTable extends AbstractTable {
  componentDidMount() {
    document.title = 'Logs | Pipes Manager';
  }

  getClassName(){
    return super.getClassName() + ' log-list-table';
  }

  _renderHead(){
    const {list: {sort}, listChangeSort} = this.props;
    return (
        <tr>
          <SortTh name="timestamp" className="col-md-1 no-wrap" state={sort} onChangeSort={listChangeSort}>Time</SortTh>
          <SortTh name="severity" className="col-md-1 no-wrap" state={sort} onChangeSort={listChangeSort}>Severity</SortTh>
          <SortTh name="topology_id" className="col-md-1 no-wrap" state={sort} onChangeSort={listChangeSort}>Topology&nbsp;ID</SortTh>
          <SortTh name="topology_name" className="col-md-1 no-wrap" state={sort} onChangeSort={listChangeSort}>Topology&nbsp;name</SortTh>
          <SortTh name="node_id" className="col-md-1 no-wrap" state={sort} onChangeSort={listChangeSort}>Node&nbsp;ID</SortTh>
          <SortTh name="node_name" className="col-md-1 no-wrap" state={sort} onChangeSort={listChangeSort}>Node&nbsp;name</SortTh>
          <SortTh name="message" className="col-md-6" state={sort} onChangeSort={listChangeSort}>Message</SortTh>
        </tr>
    );
  }

  _renderRows() {
    const {list, elements} = this.props;
    return list && list.items ? list.items.map(id => {
      const item = elements[id];
      return (
        <tr key={item.id}>
          <td className="col-md-1 no-wrap">{moment(item.date).format("DD. MM. YYYY HH:mm:ss")}</td>
          <td className="col-md-1 no-wrap">{item.severity}</td>
          <td className="col-md-1 no-wrap">{item.topology_id}</td>
          <td className="col-md-1 no-wrap">{item.topology_name}</td>
          <td className="col-md-1 no-wrap">{item.node_id}</td>
          <td className="col-md-1 no-wrap">{item.node_name}</td>
          <td className="col-md-6">{item.message}</td>
        </tr>
      )
    }) : null;
  }

  render() {
    const {list, listChangePage, listChangeFilter} = this.props;
    let rows = this._renderRows();
    if (!rows){
      rows = <tr><td colSpan={5}>No items</td></tr>;
    }
    return (
      <div className={this.getClassName()}>
        <div className="table-wrapper">
          {listChangeFilter && <LogListFilter filter={list.filter} changeFilter={listChangeFilter} />}
          <table className="table table-hover">
            <thead>
              {this._renderHead()}
            </thead>
            <tbody>
              {rows}
            </tbody>
          </table>
        </div>
        <ListPagination list={list} onPageChange={listChangePage} />
      </div>
    );
  }
}

LogListTable.propTypes = Object.assign({}, AbstractTable.propTypes, {
  elements: PropTypes.object.isRequired
});

export default StateComponent(LogListTable);
