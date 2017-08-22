import React from 'react'

import './error_state.less';

export default props =>  <div className="error-source"><span>Error{props.msg && ': '}{props.msg}</span></div>;