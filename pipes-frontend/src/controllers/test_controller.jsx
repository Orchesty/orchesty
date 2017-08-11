import React from 'react'
import FlusanecMenu from 'flusanec/src/view_models/menu';
import BaseController from './base_controller';
import MainPage from '../views/gentelella/components/page/main_page';
import ContainerType from '../view_models/content/container_type';
import BpmnIoComponent from '../views/common/bpmn/bpmn_io_component';
import Notification from '../services/notification/notification';

var test_data = require('../views/common/bpmn/test.bpmn');


class TestController extends BaseController {

  constructor(contextServices) {
    super(contextServices);
  }

  _addToMenu() {
    if (this._contextServices.containerType.type == ContainerType.WINDOW_AREA) {
      this._menuItems = [];
    }
    else {
      this._menuItems = [
        new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.SUB_MENU, 'Test', 'fa fa-lightbulb-o', null,
          new FlusanecMenu.Menu([
            new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Test page', null, () => {
              this.testPageAction()
            }),
            new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Test bpmn', null, () => {
              this.testBpmnAction()
            }),
            new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Test alert success', null, () => {
              this._contextServices.notifyService.createNotification(Notification.SUCCESS, 'Test success notification');
            }),
            new FlusanecMenu.MenuItem(FlusanecMenu.MENU_ITEM_TYPE.ACTION, 'Test alert error', null, () => {
              this._contextServices.notifyService.createNotification(Notification.ERROR, 'Test error notification');
            })
          ])
        )
      ];
    }
    this._contextServices.menu.addMenuItems(this._menuItems);
  }

  testPageAction() {
    this._contextServices.pageManager.addPage(
      <MainPage caption="Test page title">
        Test page
      </MainPage>
    )
  }

  testBpmnAction() {
    this._contextServices.pageManager.addPage(
      <MainPage caption="Test page title">
        <BpmnIoComponent scheme={test_data}/>
      </MainPage>
    )
  }
}

export default TestController;