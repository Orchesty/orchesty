import React from 'react'

import MenuItem from './MenuItem';
import './MenuCategory.less';

class MenuCategory extends React.Component {
  constructor(props) {
    super(props);
    this._itemClick = this.itemClick.bind(this);
  }

  itemClick(e){
    e.preventDefault();
    this.props.onItemClick(this.props.item);
  }

  render() {
    const item = this.props.item;
    const isSub = item.type == 'sub';
    const childrenItems = isSub ? item.items.map(item => <MenuItem item={item} key={item.id} onItemClick={this.props.onItemClick} />) : null;
    const children = childrenItems ? 
      <ul className="nav child_menu">
        {childrenItems}
      </ul> : null;
    return (
      <li className={item.open ? 'active' : ''}>
        <a onClick={this._itemClick}>
          <i className={item.icon} /> {item.caption} {isSub ? <span className="fa fa-chevron-down" /> : null}
        </a>
        {children}
      </li>
    );
  }
}

export default MenuCategory;