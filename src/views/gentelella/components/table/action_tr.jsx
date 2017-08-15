import React from 'react'
import Flusanec from 'flusanec';

import ActionButton from '../action/action_button';

class ActionTr extends Flusanec.Component {

  makeContextMenu(e){
    e.preventDefault();
    let menu = this.props.menu;
    this.props.contextServices.contextMenuService.openMenu(menu, e.clientX, e.clientY);
  }

  doMainAction(e){
    e.preventDefault();
    if (typeof this.props.mainAction == 'function'){
      this.props.mainAction();
    }
  }

  render() {
    return (
      <tr onContextMenu={e => this.makeContextMenu(e)} onClick={(e) => this.doMainAction(e)}>
        {this.props.children}
        <td>
          <ActionButton menu={this.props.menu}></ActionButton>
        </td>
      </tr>
    );
  }
}

export default ActionTr;