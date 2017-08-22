import React from 'react'
import {connect} from 'react-redux';

import * as topologyActions from '../../../actions/topology_actions';

import TopologySchema from '../components/topology_schema';
import ActionButton from '../elements/action_button';

class SchemaPage extends React.Component {
  constructor(props) {
    super(props);
    this._setActions = this.setAction.bind(this);
    this.state = {
      pageActions: null
    }
  }
  
  setAction(actions){
    this.setState({
      pageActions: actions
    });
  }

  render() {
    const {pageActions} = this.state;
    return (
      <div className="right_col" role="main">
        <div className="main-page">
          <div className="page-title">
            <div className="title_left"><h3>Topology schema</h3></div>
            <div className="title_right">
              <div className="pull-right">

                {pageActions ? <ActionButton items={pageActions} size="md" right={true} /> : null}
              </div>
            </div>
          </div>
          <div className="clearfix"></div>
          <TopologySchema schemaId={this.props.schemaId} actions={this._setActions}/>
        </div>
      </div>
    );
  }
}

export default SchemaPage;