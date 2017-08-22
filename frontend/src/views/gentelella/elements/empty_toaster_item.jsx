import React from 'react'

import './empty_toaster_item.less';

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