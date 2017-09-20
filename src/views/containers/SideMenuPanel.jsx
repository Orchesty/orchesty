import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';

import MenuCategory from 'elements/mainMenu/MenuCategory';

class SideMenuPanel extends React.Component {
  constructor(props) {
    super(props);
    this._itemClick = this.itemClick.bind(this);
  }

  itemClick(item){
    if (item.page){
      this.props.selectPage(item.page);
    } else if (item.type == 'sub') {
      this.props.toggleMainSubMenu(item.id);
    }
  }

  render() {
    let menuCategories = this.props.menu.map(item => <MenuCategory item={item} key={item.id} onItemClick={this._itemClick} />);
    return (
      <div id="sidebar-menu" className="main_menu_side hidden-print main_menu">
        <div className="menu_section">
          <h3>General</h3>
          <ul className="nav side-menu">
            {menuCategories}
          </ul>
        </div>
      </div>
    );
  }
}

function mapStateToProps(state){
  return {
    menu: state.application.mainMenu
  }
}

function mapActionsToProps(dispatch){
  return {
    selectPage: key => dispatch(applicationActions.selectPage(key)),
    toggleMainSubMenu: id => dispatch(applicationActions.toggleMainSubMenu(id))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(SideMenuPanel);