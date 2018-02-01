import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import ActionButtonPanel from 'rootApp/views/elements/actions/ActionButtonPanel';
import mainMenu from 'rootApp/config/mainMenu';

class TopMainMenu extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {mainMenu} = this.props;
    return (
      <div className="top-main-menu">
        <ActionButtonPanel anchorTag items={mainMenu} buttonClassName="top-menu-item"/>
      </div>
    );
  }
}

TopMainMenu.propTypes = {};

function mapActionsToProps(dispatch, ownProps){
  return {
    mainMenu: mainMenu(dispatch)
  }
}

export default connect(() => ({}), mapActionsToProps)(TopMainMenu);