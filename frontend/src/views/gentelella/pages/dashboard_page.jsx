import React from 'react'
import { connect } from 'react-redux';

import * as notificationActions from '../../../actions/notification_actions';


class DashboardPage extends React.Component {
  constructor(props) {
    super(props);
  }

  toasterTest(){
    this.props.addNotification('error', 'Test error notification');
  }

  render() {
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          Dashboard
          <div>
            <a className="btn btn-primary" onClick={this.toasterTest.bind(this)}>Toaster test</a>
          </div>
        </div>
      </div>
    );
  }
}

function mapActionsToProps(dispatch){
  return {
    addNotification: (type, message) => dispatch(notificationActions.addNotification(type, message))
  }
}

export default connect(() => {return {}}, mapActionsToProps)(DashboardPage);