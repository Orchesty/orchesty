import React from 'react'
import Flusanec from 'flusanec'
import MainLeftSidebar from './components/main_left_sidebar/main_left_sidebar';
import TopNavigation from './components/top_navigation/top_navigation';
import ContainerSwitch from './components/container_switch/container_switch';
import ContextMenu from './components/context_menu/context_menu';
import OpenFileDialog from '../common/files/open_file_dialog';
import Toaster from '../common/notification/toaster';

let css_bootstrap = require('./vendor/bootstrap/css/bootstrap.min.css');
let css_fontawesome = require('./vendor/font-awesome/css/font-awesome.min.css');
let css_style = require('./custom.min.css');
let custom_style = require('./style.less');

class MainApp extends Flusanec.Component {
  _initialize() {
    this._onPageChange = this.onPageChange.bind(this);
  }

  _useProps(props) {
    this.setApplication(props.application);
  }

  setApplication(application:Application) {
    this._application = application;
    this._application.pageManager.addAddPageListener(() => {this.onPageChange()});
    this._application.pageManager.addRemovePageListener(this._onPageChange);
  }

  onPageChange(page){
    this.forceUpdate();
  }

  render() {
    return (
      <div className="container body">
        <div className="main_container">
          <MainLeftSidebar application={this._application} />
          <TopNavigation containerType={this._application.containerType} />
          <ContainerSwitch containerType={this._application.containerType} contextServices={this._application.contextServices} />
        </div>
        <ContextMenu contextMenuService={this._application.contextMenuService} />
        <OpenFileDialog openFileDialogService={this._application.openFileDialogService} />
        <Toaster notifyService={this._application.notifyService} />
      </div>
    );
  }
}

export default MainApp;