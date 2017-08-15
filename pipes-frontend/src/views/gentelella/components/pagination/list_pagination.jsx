import React from 'react'
import Flusanec from 'flusanec';

import ReactPaginate from 'react-paginate';

class ListPagination extends Flusanec.Component {
  _initialize() {
    this._onDataChange = this.onDataChange.bind(this);
    this._onParamsChange = this.onParamsChange.bind(this);
  }

  _useProps(props) {
    this.list = this.props.list;
  }

  _finalization() {
    this.list = null;
  }

  onDataChange() {
    this.forceUpdate();
  }

  onParamsChange() {
    this.forceUpdate();
  }

  set list(list:SortPersistentList) {
    if (this._list != list) {
      this._list && this._list.removeDataChangeListener(this._onDataChange);
      this._list && this._list.removeLimitChangeListener(this._onParamsChange);
      this._list = list;
      this._list && this._list.addDataChangeListener(this._onDataChange);
      this._list && this._list.addLimitChangeListener(this._onParamsChange);
    }
  }

  pageChange(pageNo) {
    this._list.setLimitation(this.props.pageItemCount, pageNo * this.props.pageItemCount);
  }

  render() {
    if (this._list) {
      const pageCount = Math.ceil(this._list.total / this.props.pageItemCount);
      let pagination = null;
      if (pageCount > 1) {
        const page = Math.ceil(this._list.offset / this.props.pageItemCount);
        pagination = <div className="col col-md-8">
          <ReactPaginate pageCount={pageCount} pageRangeDisplayed={5} marginPagesDisplayed={1} initialPage={page}
            onPageChange={(data) => {this.pageChange(data.selected)}}
            containerClassName="pagination pull-right" activeClassName="active"/>
        </div>;
      }
      return <div className="row list-pagination">
        <div className="col col-md-4 text-pagination">Showing {this._list.offset + 1} to {this._list.offset + this._list.count + 1} of {this._list.total}</div>
        {pagination}</div>
    }
    return null;
  }
}

export default ListPagination;