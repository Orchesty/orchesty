import React from 'react';
import Flusanec from 'flusanec';
import FlusanecMenu from 'flusanec/src/view_models/menu';
import Window from 'flusanec/src/view_models/window/window';
import BaseController from './base_controller';
import ContainerType from '../view_models/content/container_type';

import MainPage from '../views/gentelella/components/page/main_page';
import BasicWindow from '../views/gentelella/components/window/basic_window';
import TopologyListTable from '../views/gentelella/parts/topology/topology_list_table';
import TopologyForm from '../views/gentelella/parts/topology/topology_form';
import SchemeEditor from '../views/gentelella/parts/scheme/scheme_editor';

class TopologyController extends BaseController {
  constructor(contextServices) {
    super(contextServices);
  }

  _addToMenu() {
    if (this._contextServices.containerType.type == ContainerType.WINDOW_AREA) {
      this._menuItems = [
        new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.SUB_MENU, 'Topology', 'fa fa-connectdevelop', null,
          new FlusanecMenu.Menu([
            new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Topology List', 'fa fa-list', () => {
              this.topologyListWindowAction()
            })
          ])
        )
      ]
    }
    else {
      this._menuItems = [
        new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.SUB_MENU, 'Topology', 'fa fa-connectdevelop', null,
          new FlusanecMenu.Menu([
            new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Topology List', 'fa fa-list', () => {
              this.topologyListPageAction()
            })
          ])
        )
      ]
    }
    this._contextServices.menu.addMenuItems(this._menuItems);
  }

  topologyListWindowAction() {
    const manager = this._contextServices.managers.topologyManager;
    const list = manager.getTopologyList();
    const windowContextServices = Object.assign({}, this._contextServices, {menu: null, controller: this});
    const window = new Window(BasicWindow, 'Topology list',
      <TopologyListTable contextServices={windowContextServices} topologyList={list} pageItemCount={5}
        enable={manager.enableTopology.bind(manager)} disable={manager.disableTopology.bind(manager)} />
  );
    windowContextServices.menu = window.menu;
    this._contextServices.windowManager.addWindow(window);
  }

  topologyListPageAction() {
    const manager = this._contextServices.managers.topologyManager;
    const list = manager.getTopologyList();
    const contextServices = Object.assign({}, this._contextServices, {controller: this, menu: new FlusanecMenu.Menu()});
    this._contextServices.pageManager.addPage(
      <MainPage caption="Topology list" menu={contextServices.menu}>
        <TopologyListTable contextServices={contextServices} topologyList={list} pageItemCount={20}
          enable={manager.enableTopology.bind(manager)} disable={manager.disableTopology.bind(manager)} />
      </MainPage>
    )
  }

  topologyEditAction(id){
    const promise = this._contextServices.managers.topologyManager.getTopology(id);
    this._contextServices.containerType.type == ContainerType.WINDOW_AREA ? this.topologyEditWindowAction(promise) : this.topologyEditPageAction(promise);
  }

  
  topologyEditWindowAction(topology: Topology){
    const manager = this._contextServices.managers.topologyManager;
    const windowContextServices = Object.assign({}, this._contextServices, {menu: null, controller: this});
    const window = new Window(BasicWindow, 'Topology edit',
      <TopologyForm contextServices={windowContextServices} topology={topology} update={manager.updateTopology.bind(manager)}/>
    );
    windowContextServices.menu = window.menu;
    this._contextServices.windowManager.addWindow(window);
  }

  topologyEditPageAction(topology: Topology){
    const manager = this._contextServices.managers.topologyManager;
    const contextServices = Object.assign({}, this._contextServices, {controller: this, menu: new FlusanecMenu.Menu()});
    this._contextServices.pageManager.addPage(
      <MainPage caption="Topology edit" menu={contextServices.menu}>
        <TopologyForm contextServices={contextServices} topology={topology} update={manager.updateTopology.bind(manager)}/>
      </MainPage>
    )
  }
  
  topologySchemeAction(id){
    const promise = this._contextServices.managers.topologyManager.getScheme(id);
    this._contextServices.containerType.type == ContainerType.WINDOW_AREA ? this.topologySchemeWindowAction(promise) : this.topologySchemePageAction(promise);
  }
  
  topologySchemePageAction(promise){
    const contextServices = Object.assign({}, this._contextServices, {controller: this, menu: new FlusanecMenu.Menu()});
    this._contextServices.pageManager.addPage(
      <MainPage caption="Scheme" menu={contextServices.menu}>
        <SchemeEditor contextServices={contextServices} promise={promise} />
      </MainPage>
    )
  }
}

export default TopologyController;