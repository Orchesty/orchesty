import React from 'react'
import PropTypes from 'prop-types';
import {menuItemType} from 'rootApp/types';

import './ActionIcon.less';

class ActionIcon extends React.Component {
  constructor(props) {
    super(props);
    this.makeAction = this.makeAction.bind(this);
  }

  makeAction(e) {
    const {item} = this.props;
    e.stopPropagation();
    e.preventDefault();
    if (item.type == menuItemType.ACTION && typeof item.action == 'function'){
      item.action();
    }
  }

  render() {
    const {item} = this.props;
    if (item){
      let content = null;
      switch (item.type){
        case menuItemType.ACTION:
          content = <i className={item.icon} />;
          break;
        case menuItemType.TEXT:
          content = <i className={item.icon} />;
          break;
        case menuItemType.BADGE:
          content = <span className={'badge ' + item.icon}>{item.caption}</span>;
          break;
        default:
          throw new Error(`Not supported type [${item.type}] for ActionIcon`);
      }
      return <div className={'action-icon action-icon-' + item.type} title={item.hint || item.caption} onClick={this.makeAction}>{content}</div>
    } else {
      return null;
    }
  }
}

ActionIcon.propTypes = {
  item: PropTypes.oneOfType([PropTypes.object, PropTypes.array]),
};

export default ActionIcon;