import download from 'utils/download';
import * as serverActions from 'actions/serverActions';
import config from 'rootApp/config';
import actions from 'actions';

class Application{
  constructor(store){
    this._store = store;
    this._record = null;
    this._recordUnsubscribe = null;
    this._actions = actions;
  }

  get actions(){
    return this._actions;
  }

  get dispatch(){
    return this._store.dispatch;
  }

  downloadState(){
    download(JSON.stringify(this._store.getState()), 'state.json', 'application/json');
  }

  downloadConfig(){
    download(JSON.stringify(config), 'config.json', 'application/json');
  }

  logConfig(){
    console.log(config);
  }

  _createRec(){
    this._record.states.push({
      state: this._store.getState(),
      datetime: new Date()
    });
  }

  startRec(caption){
    this._record = {
      caption,
      start: new Date(),
      finish: null,
      states: []
    };
    this._createRec();
    this._recordSubscription = this._store.subscribe(this._createRec.bind(this));
  }

  stopRec(downloadIt = false){
    this._recordSubscription();
    this._record.finish = new Date();
    if (downloadIt){
      download(JSON.stringify(this._record), 'rec.json', 'application/json');
    }

    return this._record;
  }


  _playNext(rec, index, time, speed, callback) {
    const state = rec.states[index];
    const nextTime = new Date(state ? state.datetime : rec.finish);
    setTimeout(() => {
      if (state){
        console.log(`Play state ${index} - ${time}`);
        this._store.dispatch({type: 'SET_STATE', state: state.state});
        this._playNext(rec, index + 1, nextTime, speed, callback);
      } else {
        console.log('Play - DONE');
        if (typeof callback == 'function'){
          callback();
        }
      }
    }, Math.ceil((nextTime.getTime() - time.getTime()) / speed));
  }

  play(rec, speed = 1, callback) {
    console.log('Play - ' + rec.caption);
    this._playNext(rec, 0, new Date(rec.start), speed, callback);
  }

  _openFileAndPlay(element, file, speed, callback){
    element.parentElement.removeChild(element);
    const reader = new FileReader();
    reader.onload = response => {
      this.play(JSON.parse(response.target.result), speed, callback);
    };
    reader.readAsText(file);
  }

  uploadAndPlay(speed = 1, callback){
    var elemDiv = document.createElement('div');
    elemDiv.style.cssText = 'top:0px;left:0px;position:absolute;width:250px;height:50px;z-index:100000;background:yellowgreen;';
    var input = document.createElement('input');
    input.type = 'file';
    input.style = 'margin: 10px';
    input.addEventListener('change', (e) => this._openFileAndPlay(elemDiv, e.target.files[0], speed, callback));
    elemDiv.appendChild(input);
    document.body.appendChild(elemDiv);
  }

  changeServer(serverId, callback){
    this._store.dispatch(serverActions.changeApiGatewayServer(serverId)).then(response => {
      if (typeof callback == 'function'){
        callback(response);
      }
      return response;
    })
  }
}

var application = null;

export default store => {
  if (!application){
    application = new Application(store);
    window.application = application;
  }
  
  return application;
}