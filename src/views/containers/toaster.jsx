import React from 'react'
import { connect } from 'react-redux';

import * as notificationActions from '../../actions/notificationActions';

import ToasterItem from '../elements/toaster/toasterItem';
import EmptyToasterItem from '../elements/toaster/emptyToasterItem';

import './toaster.less';

class Toaster extends React.Component {
  constructor(props) {
    super(props);
    this._closeNotification = this.closeNotification.bind(this);
  }
  
  closeNotification(notification){
    this.props.closeNotification(notification.id);
  }

  render() {
    const {notifications} = this.props;
    const items = this.props.active.map(
      (id, index) => id ?
        <ToasterItem notification={notifications[id]} onClose={this._closeNotification} key={index} /> :
        <EmptyToasterItem key={index} />
    );
    
    return (
      <div className="toaster-container">
        {items}
      </div>
    );
  }
}

function mapStateToProps(state){
  const {notification} = state;

  return {
    notifications: notification.elements,
    active: notification.active
  }
}

function mapActionsToProps(dispatch){
  return {
    closeNotification: id => dispatch(notificationActions.closeNotification(id))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(Toaster);