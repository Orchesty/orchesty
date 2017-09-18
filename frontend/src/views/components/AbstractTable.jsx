import React from 'react'
import PropTypes from 'prop-types';

import ListPagination from '../elements/table/ListPagination';


class AbstractTable extends React.Component {
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
    return null;
  }

  _renderRows(){
    return null;
  }

  render() {
    const {list, listChangePage} = this.props;
    let rows = this._renderRows();
    if (!rows){
      rows = <tr><td colSpan={6}>No items</td></tr>;
    }
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

AbstractTable.propTypes = {
  list: PropTypes.object.isRequired,
  needList: PropTypes.func.isRequired,
  listChangeSort: PropTypes.func,
  listChangePage: PropTypes.func
};

export default AbstractTable;