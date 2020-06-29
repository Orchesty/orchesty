import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import StateComponent from 'wrappers/StateComponent';
import { makeStartingPointUrl } from '../../../services/apiGatewayServer';
import { getNodeRunUrl } from '../../../actions/nodeActions';

import './SidebarStartingPointUrl.less';
import {stateType} from 'rootApp/types';

class SidebarStartingPointUrl extends React.Component {
  constructor(props, context) {
    super(props, context);
  }

  render() {
    const {node, topologyName, user} = this.props;
    if (!['cron', 'webhook', 'start'].includes(node[0].type)) {
      return <div/>;
    }

    return (
      <div className="sidebar-node-starting-point">
        <div className="url-item">
          <span className="url-label">Starting-Point URL:</span>
          <div className="url-value">{makeStartingPointUrl(getNodeRunUrl(node[0]._id,node[0].name,node[0].type,node[0].topology_id, topologyName,user))}</div>
        </div>
      </div>
    );
  }
}

function mapStateToProps(state, ownProps){
  const {node} = state;
  if (ownProps.schemaId) {
    const searched = Object.values(node.elements).filter(node => node.topology_id === ownProps.topologyId && node.schema_id === ownProps.schemaId);
    if (searched.length > 0) {
      return {
        state: stateType.SUCCESS,
        node: searched
      }
    }
  }
  return {
    state: stateType.NOT_LOADED
  }
}

const SidebarStartingPointUrlConnected = connect(mapStateToProps, null)(StateComponent(SidebarStartingPointUrl));

SidebarStartingPointUrl.propTypes = {
  topologyId: PropTypes.string.isRequired,
  topologyName: PropTypes.string.isRequired,
  schemaId: PropTypes.string.isRequired,
  user: PropTypes.string.isRequired,
};

export default SidebarStartingPointUrlConnected;