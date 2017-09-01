import React from 'react'

import ToggleLocalMenu from './toggle_local_menu';

class ActionButton extends React.Component {
  constructor(props) {
    super(props);
    this.state = {collapsed: true};
    this._toggleMenu = this.toggleMenu.bind(this);
    this._closeMenu = this.closeMenu.bind(this);
  }

  makeAction(e, item) {
    e.stopPropagation();
    e.preventDefault();
    if (typeof item.action == 'function'){
      item.action();
    }
  }

  toggleMenu(e){
    e.preventDefault();
    this.setState(previousState => {return {collapsed: !previousState.collapsed}});
  }

  closeMenu(){
    this.setState({
      collapsed: true
    });
  }

  render() {
    const {items, size, right} = this.props;
    switch (items.length){
      case 0:
        return null;

      case 1:
        return <button className={`btn btn-${size} btn-info`} type="button" aria-expanded="true" onClick={e => this.makeAction(e, items[0])}>{items[0].caption}</button>;

      default:
        return (
          <div className="btn-group">
            <button className={`btn btn-${size} btn-danger dropdown-toggle`} type="button" aria-expanded="true" onClick={this._toggleMenu}>
              Actions<span className="caret" />
            </button>
            {!this.state.collapsed && <ToggleLocalMenu items={items} right={right} onClose={this._closeMenu}></ToggleLocalMenu>}
          </div>
        )
    }
  }
}

ActionButton.defaultProps = {
  size: 'sm',
  right: false
};

export default ActionButton;