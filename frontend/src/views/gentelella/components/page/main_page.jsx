import React from 'react'
import Flusanec from 'flusanec';

import ActionButton from '../action/action_button';

class MainPage extends Flusanec.Component {
  _initialize() {
    this._onChangeMenu = this.onChangeMenu.bind(this);
  }

  _useProps(props) {
    this._caption = props.caption;
    this.menu = props.menu;
  }

  _finalization(){
    this.menu = null;
  }

  set menu(menu:Menu) {
    if (this._menu != menu) {
      this._menu && this._menu.removeItemsChangeListener(this._onChangeMenu);
      this._menu = menu;
      this._menu && this._menu.addItemsChangeListener(this._onChangeMenu);
    }
  }

  onChangeMenu() {
    this.forceUpdate();
  }

  render() {
    let title = this._caption && <div className="page-title">
        <div className="title_left">
          <h3>{this._caption}</h3>
        </div>
        <div className="title_right">
          {this._menu && <ActionButton menu={this._menu}  />}
        </div>
      </div>;

    return (
      <div className="right_col" role="main"  style={{minHeight: '1386px'}}>
        <div className="main-page">
          {title}
          <div className="clearfix"></div>
          {this.props.children}
        </div>
      </div>
    );
  }
}

export default MainPage;