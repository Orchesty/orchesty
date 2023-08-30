import React from 'react'
import {menuItemType} from 'rootApp/types';

class LocalMenuItem extends React.Component {
  constructor(props) {
    super(props);
  }

  makeAction(e){
    e.stopPropagation();
    e.preventDefault();
    const {item, onAction} = this.props;
    if (typeof item.action == 'function'){
      item.action();
      if (typeof onAction == 'function'){
        onAction(this, item);
      }
    }
  }

  render() {
    const {item} = this.props;
    switch (item.type) {
      case menuItemType.SEPARATOR:
        return <li className="divider" />;
      default:
        return <li className={item.disabled ? 'disabled' : ''}><a href="#" onClick={!item.disabled ? this.makeAction.bind(this) : null}>{item.caption}</a></li>;
    }
  }
}

export default LocalMenuItem;