import React from 'react'
import Flusanec from 'flusanec';

import ToasterItem from './toaster_item';

let toaster_less = require('./toaster.less');


class Toaster extends Flusanec.Component {
  _initialize() {
    this._onChange = this.onChange.bind(this);
  }

  _useProps(props) {
    this.notifyService = props.notifyService;
  }

  _finalization() {
    this.notifyService = null;
  }

  set notifyService(value: NotifyService){
    if (this._notifyService != value){
      this._notifyService && this._notifyService.removeChangeListener(this._onChange);
      this._notifyService = value;
      this._notifyService && this._notifyService.addChangeListener(this._onChange);
    }
  }

  onChange(){
    this.forceUpdate();
  }

  render() {
    const items = this._notifyService.items.map(notification => <ToasterItem notification={notification} />);
    return (
      <div className="toaster-container">
        {items}
      </div>
    );
  }
}

export default Toaster;