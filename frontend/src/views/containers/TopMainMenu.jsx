import React from 'react'
import {connect} from 'react-redux';
import ActionButtonPanel from 'elements/actions/ActionButtonPanel';
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

export default connect(null, mapActionsToProps)(TopMainMenu);