/**
 * Created by Admin on 11.8.2017.
 */
import Flusanec from 'flusanec';
import fetch from 'isomorphic-fetch';

class ApiGatewayServer extends Flusanec.HttpServer {
  constructor(rootUrl){
    super(rootUrl);
    this._check = this.check.bind(this);
  }
  
  check(response) {
    if (response.ok && response.status >= 200 && response.status < 300){
      return response
    } else {
      response.json()
        .then(errorData => {
          this.emitError({
            statusCode: response.status,
            statusText: response.statusText,
            url: response.url,
            response: errorData
          })
        })
        .catch(parserError => {
          this.emitError({
            statusCode: response.status,
            text: response.statusText,
            url: response.url
          })
        });
    }
  }

  send(method, relUrl, queries, data):Promise {
    let queryUrl = '';
    let headers = {
      'Accept': 'application/json'
    };
    let options = {
      method: method,
      headers: headers
    };
    if (data) {
      headers['Content-Type'] = 'application/json';
      options['body'] = JSON.stringify(data);
    }
    if (queries) {
      queryUrl = '?' + Object.keys(queries)
          .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(queries[k]))
          .join('&');
    }

    return fetch(this.rootUrl + relUrl + queryUrl)
      .then(this._check)
      .then(response => response ? response.json() : undefined)
      .catch(error => {
        this.emitError({text: error});
        return undefined;
      });
  }
  
}

export default ApiGatewayServer;