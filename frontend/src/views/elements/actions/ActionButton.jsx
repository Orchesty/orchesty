import React from 'react'
import PropTypes from 'prop-types';

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
    if (typeof item.action == 'function') {
      item.action();
    }
  }

  toggleMenu(e) {
    e.preventDefault();
    e.stopPropagation();
    this.setState(previousState => ({collapsed: !previousState.collapsed}));
  }

  closeMenu() {
    this.setState({
      collapsed: true
    });
  }

  render() {
    let {anchorTag, item, size, right, state, buttonClassName} = this.props;
    if (!item) {
      return null;
    } else {
      let caption = 'Actions';
      let items = item;
      if (!Array.isArray(item)) {
        caption = item.caption;
        if (item.hasOwnProperty('items')) {
          items = item.items;
        } else {
          items = [item];
        }
      }
      switch (items.length) {
        case 0:
          return null;

        case 1:
          return (
            <div className="btn-group">
              <StateButton
                state={state}
                processId={items[0].processId}
                size={size}
                title={items[0].tooltip}
                color={items[0].color ? items[0].color : 'primary'}
                type="button"
                aria-expanded="true"
                onClick={e => this.makeAction(e, items[0])}
                disabled={items[0].disabled}
                buttonClassName={typeof buttonClassName == 'function' ? buttonClassName(item) : buttonClassName}
                anchorTag={anchorTag}
                round={Boolean(items[0].round)}
                icon={items[0].icon ? items[0].icon : undefined}
              >{caption}</StateButton>
            </div>
          );


        default:
          const className = buttonClassName ? (typeof buttonClassName == 'function' ? buttonClassName(item) : buttonClassName) : `btn btn-${size} btn-primary`;
          return (
            <div className="btn-group">
              <a className={`${className} dropdown-toggle`} type="button" aria-expanded="true"
                onClick={this.toggleMenu}>
                {caption} {!item.noCaret && <span className="caret"/>}
              </a>
              {!this.state.collapsed && <ToggleLocalMenu items={items} right={right} onClose={this.closeMenu}/>}
            </div>
          )
      }
    }
  }
}

ActionButton.defaultProps = {
  size: 'sm',
  right: false,
  anchorTag: false,
};

ActionButton.propTypes = {
  item: PropTypes.oneOfType([PropTypes.object, PropTypes.array]),
  size: PropTypes.string,
  right: PropTypes.bool,
  state: PropTypes.string,
  buttonClassName: PropTypes.oneOfType([PropTypes.string, PropTypes.func]),
  anchorTag: PropTypes.bool.isRequired
};

export default ActionButton;