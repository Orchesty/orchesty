import React from 'react'
import Flusanec from 'flusanec';

class WindowsArea extends Flusanec.Component {
  _initialize() {
    this._onWindowChange = this.onWindowChange.bind(this);
  }

  _useProps(props) {
    this.setWindowManager(props.windowManager);
  }

  _finalization(){
    this.setWindowManager(null);
  }

  setWindowManager(windowManager:WindowManager) {
    if (this.windowManager != windowManager) {
      this.windowManager && this.windowManager.removeAddWindowListener(this._onWindowChange);
      this.windowManager && this.windowManager.removeRemoveWindowListener(this._onWindowChange);
      this.windowManager = windowManager;
      this.windowManager && this.windowManager.addAddWindowListener(this._onWindowChange);
      this.windowManager && this.windowManager.addRemoveWindowListener(this._onWindowChange);
    }
  }

  onWindowChange(window:Window) {
    this.forceUpdate();
  }

  render() {
    let windows = this.windowManager.windowList.map(window => window.component);

    return (
      <div className="row">
        {windows}
      </div>
    );
  }
}

export default WindowsArea;