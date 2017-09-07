import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as topologyActions from '../../actions/topologyActions';
import * as applicationActions from '../../actions/applicationActions';

import TopologyListTable from '../components/topology/TopologyListTable';
import ActionButtonPanel from '../elements/actions/ActionButtonPanel';

class TopologyPage extends React.Component {
  constructor(props) {
    super(props);
  }
  
  componentWillMount(){
    this.props.openTopologyList();
  }

  render() {
    const {openNewTopology, listId} = this.props;
    const pageActions = [];
    if (openNewTopology) {
      pageActions.push({
        caption: 'Create topology',
        action: openNewTopology
      });
    }
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          <div className="page-title">
            <div className="title_left"><h3>Topology</h3></div>
            <div className="title_right">
              <div className="pull-right">
                <ActionButtonPanel items={pageActions} size="md" right={true}/>
              </div>
            </div>
          </div>
          <div className="clearfix"></div>
          <TopologyListTable listId={listId} />
        </div>
      </div>
    );
  }
}

TopologyPage.propTypes = {
  listId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  openTopologyList: PropTypes.func,
  closeTopologyList: PropTypes.func,
  openNewTopology: PropTypes.func
};

function mapStateToProps(state, ownProps){
  return {
    listId: ownProps.pageKey
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    openNewTopology: () => dispatch(applicationActions.openModal('topology_edit', {addNew: true})),
    openTopologyList: () => dispatch(topologyActions.openTopologyList(ownProps.pageKey)),
    closeTopologyList: () => dispatch(topologyActions.closeTopologyList(ownProps.pageKey))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyPage);