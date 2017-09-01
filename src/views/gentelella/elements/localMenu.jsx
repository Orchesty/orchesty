import React from 'react'

import LocalMenuItem from './localMenuItem';

class LocalMenu extends React.Component {
  constructor(props) {
    super(props);
  }

  actionMade(val){
    const {onAction} = this.props;
    if (typeof onAction == 'function'){
      onAction(this, val);
    }
  }

  render() {
    const {items, right} = this.props;
    const menuItems = items.map((item, index) => <LocalMenuItem onAction={this.actionMade.bind(this)} item={item} key={index}></LocalMenuItem>);
    const alignClassName = right ? ' dropdown-menu-right' : '';
    return (
      <ul className={`dropdown-menu${alignClassName}`}>
        {menuItems}
      </ul>
    );
  }
}

export default LocalMenu;