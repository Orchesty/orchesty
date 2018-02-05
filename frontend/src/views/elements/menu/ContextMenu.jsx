import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import ToggleLocalMenu from './ToggleLocalMenu';
import * as applicationActions from 'rootApp/actions/applicationActions';

import './ContextMenu.less';

class ContextMenu extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {x, y, ...passProps} = this.props;
    return (
      <div className="context-menu" style={{left: x, top: y}}>
        <ToggleLocalMenu {...passProps} />
      </div>
    );
  }
}

ContextMenu.propTypes = {
  right: PropTypes.bool,
  items: PropTypes.array.isRequired,
  componentKey: PropTypes.string.isRequired,
  x: PropTypes.number.isRequired,
  y: PropTypes.number.isRequired
};

function mapStateToProps(state){
  const {application: {contextMenu}} = state;
  return {
    componentKey: `${contextMenu.componentKey}.context-menu`,
    x: contextMenu.x,
    y: contextMenu.y
  }
}

function mapActionsToProps(dispatch){
  return {
    onClose: () => dispatch(applicationActions.closeContextMenu())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(ContextMenu);