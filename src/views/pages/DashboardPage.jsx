import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';

class DashboardPage extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {selectPage} = this.props;
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          <div>
            Welcome, continue to topology list <br/>
            <a className="btn btn-primary" onClick={() => selectPage('topology_list')}>Go To Topology List</a>
          </div>
        </div>
      </div>
    );
  }
}

function mapActionsToProps(dispatch){
  return {
    selectPage: type => dispatch(applicationActions.selectPage(type)),
  }
}

export default connect(() => {return {}}, mapActionsToProps)(DashboardPage);