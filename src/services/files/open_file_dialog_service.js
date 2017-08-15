import EventEmitter from 'events';

class OpenFileDialogService extends EventEmitter{

  constructor(){
    super();
  }

  openFile(){
    return new Promise((resolve, reject) => {
      this.emit('open_dialog', this, (file) => {
        if (file) {
          const reader = new FileReader();
          reader.onload = e => {
            resolve({file, content: e.target.result});
          };
          reader.readAsText(file);
        }
        else{
          reject();
        }
      });
    });
  }

  addOpenDialogListener(callback){
    this.on('open_dialog', callback)
  }

  removeOpenDialogListener(callback){
    this.removeListener('open_dialog', callback);
  }
}

export default OpenFileDialogService;