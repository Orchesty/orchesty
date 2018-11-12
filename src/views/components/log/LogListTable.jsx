import React from 'react'
import PropTypes from 'prop-types';
import Moment from 'react-moment';
import AbstractTable from 'components/AbstractTable';
import StateComponent from 'wrappers/StateComponent';
import SortTh from 'elements/table/SortTh';
import './LogListTable.less';
import ListPagination from 'elements/table/ListPagination';
import LogListFilter from 'components/log/LogListFilter';

class LogListTable extends AbstractTable {
  constructor(props) {
    super(props);
  }

  getClassName(){
    return super.getClassName() + ' log-list-table';
  }

  _renderHead(){
    const {list: {sort}, listChangeSort} = this.props;
    return (
        <tr>
          <SortTh className="no-wrap" name="timestamp" state={sort} onChangeSort={listChangeSort}>Time</SortTh>
          <SortTh className="no-wrap" name="severity" state={sort} onChangeSort={listChangeSort}>Severity</SortTh>
          <SortTh name="message" state={sort} onChangeSort={listChangeSort}>Message</SortTh>
          <SortTh name="topology_name" className="no-wrap" state={sort} onChangeSort={listChangeSort}>Topology name</SortTh>
          <SortTh name="node_name" className="no-wrap" state={sort} onChangeSort={listChangeSort}>Node name</SortTh>
        </tr>
    );
  }

  _renderRows() {
    const {list, elements} = this.props;
    return list && list.items ? list.items.map(id => {
      const item = elements[id];
      return (
        <tr key={item.id}>
          <td className="no-wrap"><Moment format="DD. MM. YYYY HH:mm:ss">{item.time}</Moment></td>
          <td className="no-wrap">{item.severity}</td>
          <td>{item.message}</td>
          <td className="no-wrap">{item.topology_name}</td>
          <td className="no-wrap">{item.node_name}</td>
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