import React from 'react'
import { connect } from 'react-redux';

import Error404Page from '../pages/error404Page';
import DashboardPage from '../pages/dashboardPage';
import TopologyPage from '../pages/topologyPage';
import SchemaPage from '../pages/schemaPage';

import './activePage.less';

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