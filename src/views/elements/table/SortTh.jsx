import React from 'react'

import './SortTh.less';

class SortTh extends React.Component {
  constructor(props) {
    super(props);
    this._onClick = this.onClick.bind(this);
  }

  getSortType(){
    const {state, name} = this.props;
    return state && state.key == name ? (typeof state.type == 'string' && state.type.toLowerCase() == 'desc' ? 'desc' : 'asc') : false;
  }

  onClick(e){
    e.preventDefault();
    const {onChangeSort, name} = this.props;
    const sortType = this.getSortType();
    if (typeof onChangeSort == 'function'){
      onChangeSort({
        key: name,
        type: sortType == 'asc' ? 'desc' : 'asc'
      });
    }
  }

  render() {
    const {onChangeSort, children, className} = this.props;
    const sortType = this.getSortType();
    const icon = sortType ? (sortType == 'desc' ? <i className="fa fa-sort-amount-desc" /> : <i className="fa fa-sort-amount-asc" />) : false;
    if (onChangeSort) {
      return (
        <th className={className ? `sort-th ${className}` : "sort-th"} onClick={this._onClick}>
          {children}
          <div className="direction-icon">{icon} </div>
        </th>
      );
    } else {
      return <th>{children}</th>
    }
  }
}

export default SortTh;