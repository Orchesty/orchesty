import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import ToggleLocalMenu from 'elements/menu/ToggleLocalMenu';
import StateButton from 'elements/input/StateButton';

class ActionButton extends React.Component {
  constructor(props) {
    super(props);
    this.state = {collapsed: true};
    this.toggleMenu = this.toggleMenu.bind(this);
    this.closeMenu = this.closeMenu.bind(this);
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
    e.stopPropagation();
    this.setState(previousState => {return {collapsed: !previousState.collapsed}});
  }

  closeMenu(){
    this.setState({
      collapsed: true
    });
  }

  render() {
    let {item, size, right} = this.props;
    if (!item){
      return null;
    } else {
      let caption = 'Actions';
      let items = item;
      if (!Array.isArray(item)){
        caption = item.caption;
        if (item.hasOwnProperty('items')){
          items = item.items;
        } else {
          items = [item];
        }
      }
      switch (items.length){
        case 0:
          return null;

        case 1:
          const state = this.props.hasOwnProperty('state') ? this.props.state : items[0].state;
          return (
            <div className="btn-group">
              <StateButton state={state} size={size} color="info" type="button" aria-expanded="true" onClick={e => this.makeAction(e, items[0])} disabled={items[0].disabled}>{caption}</StateButton>
            </div>
          );

        default:
          return (
            <div className="btn-group">
              <button className={`btn btn-${size} btn-danger dropdown-toggle`} type="button" aria-expanded="true" onClick={this.toggleMenu}>
                {caption}<span className="caret" />
              </button>
              {!this.state.collapsed && <ToggleLocalMenu items={items} right={right} onClose={this.closeMenu}></ToggleLocalMenu>}
            </div>
          )
      }
    }
  }
}

ActionButton.defaultProps = {
  size: 'sm',
  right: false
};

ActionButton.propTypes = {
  item: PropTypes.oneOfType([PropTypes.object, PropTypes.array]),
  size: PropTypes.string,
  right: PropTypes.bool,
  state: PropTypes.string
};

function mapStateToProps(state, ownProps) {
  if (ownProps.item && ownProps.item.processId){
    const {process} = state;
    return {
      state: process[ownProps.item.processId]
    }
  } else {
    return {};
  }
}

export default connect(mapStateToProps)(ActionButton);