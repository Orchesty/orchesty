export default (topology) => {
  if (topology.visibility === 'draft') {
    return {
      label: 'default',
      title: 'Draft',
    };
  } else if (topology.visibility === 'public') {
    if (topology.enabled) {
      return {
        label: 'success',
        title: 'Enabled',
      };
    }
    return {
      label: 'warning',
      title: 'Disabled',
    };
  }
  return {
    label: 'danger',
    title: 'Invalid state',
  };
};
