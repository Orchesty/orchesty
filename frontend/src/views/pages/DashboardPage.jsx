import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';
import Panel from 'rootApp/views/wrappers/Panel';

const TestComp = () => <div>Test content</div>;

const TestPanel = Panel(TestComp);

class DashboardPage extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {openPage, componentKey} = this.props;
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          <div>
            Welcome, continue to topology list <br/>
            <a className="btn btn-primary" onClick={() => openPage('topology_list')}>Go To Topology List</a>
            <TestPanel componentKey={componentKey} title="Test panel" icon="fa fa-user"/>
          </div>
        </div>
      </div>
    );
  }
}

function mapActionsToProps(dispatch){
  return {
    openPage: type => dispatch(applicationActions.openPage(type)),
  }
}

export default connect(null, mapActionsToProps)(DashboardPage);