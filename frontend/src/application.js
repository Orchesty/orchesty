/**
 * Created by Admin on 22.3.2017.
 */
import Flusanec from 'flusanec';
import FlusanecMenu from 'flusanec/src/view_models/menu'
import ContainerType from './view_models/content/container_type';
import WindowManager from 'flusanec/src/view_models/window/window_manager';
import SinglePageManager from './_flusanec/view_models/page/single_page_manager';
import TestController from './controllers/test_controller';
import TopologyDataSource from './models/topology/topology_data_source';
import TopologyManager from './models/topology/topology_manager';
import TopologyController from './controllers/topology_controller';
import FakeServer from './services/servers/fake_server';
import ApiGatewayServer from './services/servers/api_gateway_server';
import OpenFileDialogService from './services/files/open_file_dialog_service';
import NotifyService from './services/notification/notify_service';
import ContextMenuService from 'flusanec/src/services/context_menu/context_menu_service';

import server_fake_data from './data/fake/server.json';
import server_fake_files from './data/fake/files_loader';


class Application{
  constructor(){
    this._name = 'Pipes';
    this._server = new FakeServer(server_fake_data, server_fake_files);
    //this._server = new ApiGatewayServer('http://pipes-example:81/gateway/api');
    this._notifyService = new NotifyService(10000);
    this._containerType = new ContainerType(ContainerType.PAGE);
    this._menu = new FlusanecMenu.Menu([], true);
    this._openFileDialogService = new OpenFileDialogService();
    this._contextMenuService = new ContextMenuService();
    this._windowManager = new WindowManager();
    this._pageManager = new SinglePageManager();

    this._dataSourceContainer = new Flusanec.DataSourceContainer([
      new TopologyDataSource(this._server)
    ]);

    this._contextServices = {
      pageManager: this._pageManager,
      windowManager: this._windowManager,
      menu: this._menu,
      containerType: this._containerType,
      contextMenuService: this._contextMenuService,
      openFileDialogService: this._openFileDialogService,
      notifyService: this._notifyService,
      managers: {
        topologyManager: new TopologyManager(this._dataSourceContainer)
      }
    };
    this._testController = new TestController(this._contextServices);
    this._topologyController = new TopologyController(this._contextServices);
  }
  
  get name(){
    return this._name;
  }

  get menu(): Menu{
    return this._menu;
  }

  get pageManager(): SinglePageManager{
    return this._pageManager;
  }

  get server(): HttpServer{
    return this._server;
  }

  get containerType(): ContainerType{
    return this._containerType;
  }

  get contextServices(){
    return this._contextServices;
  }

  get contextMenuService(): ContextMenuService{
    return this._contextMenuService;
  }

  get openFileDialogService(): OpenFileDialogService{
    return this._openFileDialogService;
  }

  get notifyService(): NotifyService{
    return this._notifyService;
  }
}

export default Application;