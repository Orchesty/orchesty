import React from 'react'
import {connect} from 'react-redux';

import * as topologyActions from '../../../actions/topology_actions';

import TopologyListTable from '../components/topology_list_table';

class TopologyPage extends React.Component {
  constructor(props) {
    super(props);
  }
  
  componentWillMount(){
    this.props.openTopologyList();
  }

  render() {
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          <div className="page-title">
            <div className="title_left"><h3>Topology</h3></div>
            <div className="title_right"></div>
          </div>
          <div className="clearfix"></div>
          <TopologyListTable listId={this.props.listId} />
        </div>
      </div>
    );
  }
}

function mapStateToProps(state){
  const {application} = state;
  return {
    listId: application.selectedPage.data ? application.selectedPage.data.topologyListId : null
  }
}

function mapActionsToProps(dispatch){
  return {
    openTopologyList: () => dispatch(topologyActions.openTopologyList()),
    closeTopologyList: id => dispatch(topologyActions.closeTopologyList(id))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyPage);