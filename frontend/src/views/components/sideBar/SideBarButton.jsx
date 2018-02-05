import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import * as applicationActions from 'rootApp/actions/applicationActions';

import './SideBarButton.less';

class SideBarButton extends React.Component {
  constructor(props) {
    super(props);
    this.buttonClick = this.buttonClick.bind(this);
  }

  buttonClick(e){
    e.preventDefault();
    this.props.toggle();
  }

  render() {
    const {showSideBar} = this.props;
    return (
      <div className="nav sidebar-toggle">
        <a onClick={this.buttonClick}><i className={'fa fa-angle-double-' + (showSideBar ? 'left' : 'right')} /></a>
      </div>
    );
  }
}

SideBarButton.propTypes = {
  showSideBar: PropTypes.bool.isRequired,
  toggle: PropTypes.func.isRequired
};

function mapStateToProps(state){
  const {application} = state;
  return {
    showSideBar: application.showSideBar
  }
}

function mapActionsToProps(dispatch){
  return {
    toggle: () => dispatch(applicationActions.leftSidebarToggle())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(SideBarButton);