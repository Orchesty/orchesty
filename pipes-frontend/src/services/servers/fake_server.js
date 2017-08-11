import Flusanec from 'flusanec';

class FakeServer extends Flusanec.HttpServer{
  constructor(fakeData, fakeFiles){
    super();
    this._fakeData = fakeData;
    this._fakeFiles = fakeFiles;
  }
  
  _getFakeFile(file){
    return this._fakeFiles[file];
  }
  
  send(method, relUrl, queries, data):Promise{
    return new Promise((resolve, reject) => {
      if (this._fakeData[method] && this._fakeData[method][relUrl]){
        let fakeRec = this._fakeData[method][relUrl];
        setTimeout(() => {
          if (Math.random() < (fakeRec.error_rate || 0)){
            this.emitError({
              statusCode: 400,
              test: 'Fake generated error',
              url: relUrl
            });
            resolve();
          }
          else{
            resolve(fakeRec.data ? fakeRec.data : this._getFakeFile(fakeRec.file));
          }
        }, Math.floor(Math.random() * (fakeRec.max_delay || 0)));
      }
      else{
        this.emitError({
          statusCode: 404,
          test: 'Not found',
          url: relUrl
        });
        resolve();
      }
    });
  }

  downloadFile(relUrl, type, queries){
    return this.send('GET', relUrl, queries);
  }

}

export default FakeServer;