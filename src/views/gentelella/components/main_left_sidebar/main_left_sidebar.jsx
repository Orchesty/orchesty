import React from 'react'
import Flusanec from 'flusanec';
import MainMenu from '../main_menu/main_menu';

class MainLeftSidebar extends Flusanec.Component {
  _initialize() {
  }

  _useProps(props) {
    this.setApplication(props.application);
  }

  _finalization(){
    this.setApplication(null);
  }
  
  setApplication(application: Application){
    this._application = application;
  }

  render() {
    return (
      <div className="col-md-3 left_col">
        <div className="navbar nav_title" style={{border: 0}}>
          <a href="#" className="site_title"><i className="fa fa-connectdevelop"></i> <span>{this._application.name}</span></a>
        </div>
        <div className="clearfix"></div>
        <MainMenu menu={this._application.menu}></MainMenu>
      </div>
    );
  }
}

export default MainLeftSidebar;