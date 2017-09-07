import React from 'react'
import {connect} from 'react-redux';

import * as topologyActions from '../../actions/topologyActions';

import TopologyListTable from '../components/topology/TopologyListTable';

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

function mapStateToProps(state, ownProps){
  return {
    listId: ownProps.pageKey
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    openTopologyList: () => dispatch(topologyActions.openTopologyList(ownProps.pageKey)),
    closeTopologyList: () => dispatch(topologyActions.closeTopologyList(ownProps.pageKey))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyPage);