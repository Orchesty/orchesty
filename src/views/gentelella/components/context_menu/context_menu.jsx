import React from 'react'
import ContextMenuComponent from 'flusanec/src/components/menu/context_menu_component';
import LocalMenu from '../local_menu/local_menu';

class ContextMenu extends ContextMenuComponent {

  renderMenu(selfFunction, menu, x, y){
    return (
      <div ref={selfFunction} className="dropdown open context-menu" style={{left: x, top: y}}>
        <LocalMenu menu={menu} visible={true} onAction={this._close}></LocalMenu>
      </div>
    );
  }
}

export default ContextMenu;



