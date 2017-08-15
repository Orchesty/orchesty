import React from 'react'
import Flusanec from 'flusanec';

import FlusanecMenu from 'flusanec/src/view_models/menu';
import ActionPanel from '../action/action_panel';

class BasicWindow extends Flusanec.Component {
  _initialize(){
    this._onWindowChange = this.onWindowChange.bind(this);
    this._menuItems = [
      new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Close', 'fa fa-close', () => this._window.release())
    ]
  }

  _useProps(props){
    this.window = props.window;
  }

  _finalization(){
    this.window = null;
  }

  set window(window: Window){
    if (this._window != window){
      this._window && this._window.removeChangeListener(this._onWindowChange);
      this._window && this._window.menu.removeMenuItems(this._menuItems);
      this._window = window;
      this._window && this._window.menu.addMenuItems(this._menuItems);
      this._window && this._window.addChangeListener(this._onWindowChange);
    }
  }

  onWindowChange(){
    this.forceUpdate();
  }

  onCloseClick(e){
    e.preventDefault();
    this._window.release();
  }

  render() {
    return (
      <div className="col-md-6">
        <div className="x_panel">
          <div className="x_title">
            <h2>{this._window.caption}</h2>
            <ActionPanel menu={this._window.menu} className="nav navbar-right panel_toolbox" />
            <div className="clearfix"></div>
          </div>
          <div className="x_content">
            {this.props.children}
          </div>
        </div>
      </div>
    );
  }
}

export default BasicWindow;