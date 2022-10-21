module.exports = {
  sidebar: [
    group('get-started', 'installation', 'architecture', 'integration', 'orchestration', 'admin', 'SDK', 'Orchesty-Store'),
    group('tutorials', 'getting-started-with-tutorials','first-process', 'SDK-settings','custom-node', 'basic-connector','basic-application', 'oauth2-application', 'introduction-to-batch', 'pagination', 'stored-data', 'scheduled-process',  'webhooks'),
    group('documentation', 'process-topology', 'workers', 'processDto', 'starting-events', 'editor', 'applications-and-connectors', 'form', 'results-evaluation', 'routing', 'batch', 'data-storage', 'limiter', 'trash', 'performance-optimization-and-ordering', 'logs')
  ]
};

function group(id, ...itemIds) {
  return {
    label: label(id),
    type: 'category',
    link: {
      type: 'generated-index'
    },
    items: itemIds.map(it => item(`${id}/${it}`)),
  };
}

function item(id) {
  return {
    id,
    type: 'doc',
    label: label(id),
  };
}

function label(id) {
  const label = id.split('/');
  let name = label.pop();
  name = name.replace(/-/g, ' ');

  return name[0].toUpperCase() + name.substring(1);
}
