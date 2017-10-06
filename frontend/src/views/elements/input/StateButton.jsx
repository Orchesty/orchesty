import React from 'react';
import Loading from 'react-loading';
import PropTypes from 'prop-types';

import {stateType} from 'rootApp/types';

import './StateButton.less';
import ProcessToState from "wrappers/ProcessToState";

const sizes = {
  xs: 14,
  sm: 16,
  md: 18,
  lg: 22
};

function StateButton(props){
  const {type, color, size, state, children, disabled, ...passProps} = props;
  const px = sizes[size];
  const inner = state == stateType.LOADING ? <Loading className="loading-icon" type="spinningBubbles" color="#000000" width={px} height={px} delay={100} /> : null;
  return (
    <button className={`btn btn-${color} btn-${size} state-button`} type={type} disabled={disabled || state == stateType.LOADING} {...passProps}>{inner}{children}</button>
  )
}

StateButton.defaultProps = {
  type: 'submit',
  size: 'md',
  color: 'primary'
};

StateButton.propTypes = {
  type: PropTypes.oneOf(['submit', 'button', 'reset']).isRequired,
  color: PropTypes.string.isRequired,
  size: PropTypes.oneOf(Object.keys(sizes)).isRequired,
  state: PropTypes.string
};

export default ProcessToState(StateButton);
