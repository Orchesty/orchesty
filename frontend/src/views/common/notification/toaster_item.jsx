import React from 'react'
import Flusanec from 'flusanec';

let toaster_less = require('./toaster_item.less');


class ToasterItem extends Flusanec.Component {
  _initialize() {
  }

  _useProps(props) {

  }

  _finalization() {

  }

  click(e){
    e.preventDefault();
    this.props.notification.release();
  }

  render() {
    return (
      <div className={'toaster toaster-' + this.props.notification.type} onClick={e => {this.click(e)}}>
        <button type="button" className="toaster-close-button">x</button>
        <div className="toaster-message">{this.props.notification.msg}</div>
      </div>
    );
  }
}

export default ToasterItem;