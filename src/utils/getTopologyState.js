export default topology => {
  if (topology.visibility === 'draft'){
    return {
      label: 'default',
      title: 'Draft'
    };
  } else if (topology.visibility === 'public'){
    if (topology.enabled){
      return {
        label: 'success',
        title: 'Enabled'
      }
    } else {
      return {
        label: 'warning',
        title: 'Disabled'
      }
    }
  } else {
    return {
      label: 'danger',
      title: 'Invalid state'
    }
  }
}