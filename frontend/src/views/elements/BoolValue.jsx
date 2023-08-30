import React from 'react';

import './BoolValue.less';

export default props => {
  const {color, value, ...passProps} = props;
  return (<span className={color ? ('bool-color-' + (value ? 'green' : 'red')) : ''} {...passProps}>{value ? 'Yes' : 'No'}</span>);
};