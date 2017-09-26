import React from 'react';

import './BoolValue.less';

export default props => <span className={props.color ? ('bool-color-' + (props.value ? 'green' : 'red')) : ''}>{props.value ? 'Yes' : 'No'}</span>;