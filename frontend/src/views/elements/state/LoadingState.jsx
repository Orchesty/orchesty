import React from 'react';
import Loading from 'react-loading';

import './LoadingState.less';

export default props => <div className="loading-source"><Loading type="spinningBubbles" color="#000000" height={24} width={24} delay={200}/></div>;