import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {menuItemType} from 'rootApp/types';
import ActionButton from 'elements/actions/ActionButton';
import * as authActions from 'actions/authActions';

class UserMenu extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {user, buttonClassName, logout} = this.props;
    if (user) {
      const menu = {
        type: menuItemType.SUB_MENU,
        caption: user.email,
        items: [
          {
            type: menuItemType.ACTION,
            caption: 'Log Out',
            action: logout
          }
        ]
      };
      return (
        <div className="user-menu">
          <ActionButton anchorTag item={menu} buttonClassName={buttonClassName} right/>
        </div>
      );
    } else {
      return null;
    }
  }
}

UserMenu.propTypes = {
  user: PropTypes.object,
  buttonClassName: PropTypes.string,
  logout: PropTypes.func.isRequired
};

function mapStateToProps(state) {
  const {auth} = state;
  return {
    user: auth.user
  }
}

function mapActionsToProps(dispatch) {
  return {
    logout: () => dispatch(authActions.logout())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(UserMenu);