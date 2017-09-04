import React from 'react'

import './ErrorState.less';

export default props =>  <div className="error-source"><span>Error{props.msg && ': '}{props.msg}</span></div>;