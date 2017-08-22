import React from 'react'
import { connect } from 'react-redux';

import Error404Page from '../pages/error_404_page';
import DashboardPage from '../pages/dashboard_page';
import TopologyPage from '../pages/topology_page';
import SchemaPage from '../pages/schema_page';

import './active_page.less';

class ActivePage extends React.Component {
  render() {
    const {page} = this.props;
    switch (page.key){
      case 'dashboard':
        return <DashboardPage {...page.args}/>;
      case 'topology_list':
        return <TopologyPage {...page.args}/>;
      case 'topology_schema':
        return <SchemaPage {...page.args}/>;
      default:
        return <Error404Page />;
    }
  }
}

function mapStateToProps(state){
  const {application} = state;

  return {
    page: application.selectedPage
  }
}

export default connect(mapStateToProps)(ActivePage);