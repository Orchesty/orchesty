import React from 'react'
import Flusanec from 'flusanec';
import ContainerTypeButton from '../container_type_button/container_type_button';

class TopNavigation extends Flusanec.Component {
  _initialize() {
    this._onChangeContainerType = this.onChangeContainerType.bind(this);
    this._containerType = null;
  }

  _useProps(props) {
    this.application = props.application;
    this.containerType = props.containerType;
  }

  _finalization(){
    this.application = null;
    this.containerType = null;
  }
  
  set application(application: Application){
    this._application = application;
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
  
  switchContainerType(e){
    e.preventDefault();
    this._containerType.switchNext();
  }

  render(){
    let buttonContainerType = this._containerType &&
      <li><ContainerTypeButton type={this._containerType.type} onClick={this.switchContainerType.bind(this)}></ContainerTypeButton></li>;

    return (
      <div className="top_nav">
        <div className="nav_menu">
          <nav>
            <div className="nav toggle">
              <a id="menu_toggle"><i className="fa fa-bars"></i></a>
            </div>

            <ul className="nav navbar-nav navbar-right">
              <li className="">
                <a href="javascript:;" className="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                  <img src="images/img.jpg" alt="" />John Doe
                    <span className=" fa fa-angle-down"></span>
                </a>
              </li>

              <li role="presentation" className="dropdown">
                <a href="javascript:;" className="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                  <i className="fa fa-envelope-o"></i>
                  <span className="badge bg-green">6</span>
                </a>
              </li>
              {buttonContainerType}
            </ul>
          </nav>
        </div>
      </div>
    );
  }
}

export default TopNavigation;