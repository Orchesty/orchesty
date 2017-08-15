import React from 'react'
import Flusanec from 'flusanec';
import ContainerType from '../../../../view_models/content/container_type';
import WindowsArea from '../window/windows_area';

class ContainerSwitch extends Flusanec.Component {
  _initialize() {
    this._onChangeContainerType = this.onChangeContainerType.bind(this);
  }

  _useProps(props) {
    this.containerType = props.containerType;
  }

  _finalization() {
    this.containerType = null;
  }

  set containerType(value: ContainerType){
    if (this._containerType != value){
      this._containerType && this._containerType.removeChangeListener(this._onChangeContainerType);
      this._containerType = value;
      this._containerType && this._containerType.addChangeListener(this._onChangeContainerType);
    }
  }

  onChangeContainerType(){
    this.forceUpdate();
  }
  

  render() {
    if (this._containerType.type == ContainerType.PAGE) {
      return <div>{this.props.contextServices.pageManager.pageList[0]}</div>
    }
    else {
      return <div className="right_col" role="main"  style={{minHeight: '1386px'}}>
        <WindowsArea windowManager={this.props.contextServices.windowManager}></WindowsArea>
      </div>;
    }
  }
}

export default ContainerSwitch;