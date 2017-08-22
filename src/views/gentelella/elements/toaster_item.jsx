import React from 'react'

import './toaster_item.less';

class ToasterItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {notification, onClose} = this.props;
    return (
      <div className={'toaster toaster-' + notification.type} onClick={e => {e.preventDefault(); onClose(notification)}}>
        <button type="button" className="toaster-close-button">x</button>
        <div className="toaster-message">{notification.message}</div>
      </div>
    );
  }
}

export default ToasterItem;