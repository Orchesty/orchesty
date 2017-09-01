import React from 'react'
import ReactPaginate from 'react-paginate';

import {stateType, listType} from '../../../types';

import './listPagination.less';

class ListPagination extends React.Component {
  constructor(props) {
    super(props);
    this._pageChange = this.pageChange.bind(this);
  }

  pageChange(data){
    const {onPageChange} = this.props;
    if (typeof onPageChange == 'function'){
      onPageChange(data.selected);
    }
  }

  render() {
    const {list} = this.props;
    if (list && list.state == stateType.SUCCESS && list.type == listType.PAGINATION){
      const pageCount = Math.ceil(list.total / list.pageSize);
      let pagination = null;
      if (pageCount > 1){
        const page = Math.ceil(list.offset / list.pageSize);
        pagination =
          <div className="col col-md-8">
            <ReactPaginate
              pageCount={pageCount}
              pageRangeDisplayed={5}
              marginPagesDisplayed={1}
              initialPage={page}
              onPageChange={this._pageChange}
              containerClassName="pagination pull-right"
              activeClassName="active"
            />
          </div>;
      }
      return (
        <div className="row list-pagination">
          <div className="col col-md-4 text-pagination">Showing {list.offset + 1} to {list.offset + list.count + 1} of {list.total}</div>
          {pagination}
        </div>
      );
    }
    else {
      return null;
    }
  }
}

export default ListPagination;