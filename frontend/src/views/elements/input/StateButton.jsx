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
  const {round, icon, anchorTag, type, color, size, state, children, disabled, buttonClassName, ...passProps} = props;
  const px = sizes[size];
  const inner = state == stateType.LOADING ? <Loading className="loading-icon" type="spinningBubbles" color="#000000" width={px} height={px} delay={100} /> : null;
  const className = buttonClassName ? buttonClassName : `btn btn-${color} btn-${size}` + (round ? ' btn-round' : '') ;
  const Tag = anchorTag ? 'a' : 'button';
  const content = icon ? <i className={icon} /> : children;
  return (
    <Tag className={`${className} state-button`} type={type} disabled={disabled || state == stateType.LOADING} {...passProps}>{inner}{content}</Tag>
  );
}

StateButton.defaultProps = {
  type: 'submit',
  size: 'md',
  color: 'primary',
  anchorTag: false,
  round: false
};

StateButton.propTypes = {
  type: PropTypes.oneOf(['submit', 'button', 'reset']).isRequired,
  color: PropTypes.string.isRequired,
  size: PropTypes.oneOf(Object.keys(sizes)).isRequired,
  state: PropTypes.string,
  buttonClassName: PropTypes.string,
  anchorTag: PropTypes.bool.isRequired,
  round: PropTypes.bool.isRequired,
  icon: PropTypes.string
};

export default ProcessToState(StateButton);
