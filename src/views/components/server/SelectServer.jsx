import React from 'react';
import {connect} from 'react-redux';
import PropTypes from 'prop-types';

import config from 'rootApp/config';
import * as serverActions from 'actions/serverActions';

import './SelectServer.less';

class SelectServer extends React.Component {
  constructor(props) {
    super(props);
  }

  selectServer(server, e){
    e.preventDefault();
    this.props.changeServer(server);
  }

  render() {
    if (config.params.allowChangeServer) {
      const {servers} = config.servers.apiGateway;
      const {selected} = this.props;
      const items = Object.keys(servers).map(key => {
        const server = servers[key];
        return <li key={key} className={selected == key ? 'active' : ''}><a href="#"
          onClick={this.selectServer.bind(this, key)}>{server.caption}</a></li>;
      });
      return (
        <ul className="select-server">
          {items}
        </ul>
      );
    } else {
      return null;
    }
  }
}

SelectServer.propTypes = {
  selected: PropTypes.string.isRequired,
  changeServer: PropTypes.func.isRequired
};

function mapStateToProps(state, ownProps) {
  const {server} = state;
  return {
    selected: server.apiGateway
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    changeServer: server => dispatch(serverActions.changeApiGatewayServer(server))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(SelectServer);