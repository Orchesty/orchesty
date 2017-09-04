import React from 'react'

import './EmptyToasterItem.less';

class EmptyToasterItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="toaster toaster-empty" />
    );
  }
}

export default EmptyToasterItem;