const HttpSyncWorker = require('./http-sync-worker');

class PipesSyncWorker extends HttpSyncWorker {

  constructor(host, serviceId, opts) {
    const url = `${host}/api/nodes/${serviceId}/process`;

    super('POST', url, opts);
  }
}

exports.PipesSyncWorker = PipesSyncWorker;
exports.factory = config => new PipesSyncWorker(config.host, config.service, config.request_opts);
