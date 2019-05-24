import { menuItemType } from 'rootApp/types';
import * as applicationActions from 'rootApp/actions/applicationActions';

export default dispatch => [
  {
    type: menuItemType.SUB_MENU,
    caption: 'File',
    items: [
      {
        type: menuItemType.ACTION,
        caption: 'New topology',
        action: () => dispatch(applicationActions.openModal('topology_edit', { addNew: true })),
      },
      {
        type: menuItemType.ACTION,
        caption: 'New category',
        action: () => dispatch(applicationActions.openModal('category_edit', { addNew: true })),
      },
    ],
  },
  {
    type: menuItemType.ACTION,
    caption: 'Notification Settings',
    action: () => dispatch(applicationActions.openPage('notification_settings_list')),
  },
  {
    type: menuItemType.ACTION,
    caption: 'Human Tasks',
    action: () => dispatch(applicationActions.openPage('human_tasks_list')),
  },
  {
    type: menuItemType.ACTION,
    caption: 'Cron Tasks',
    action: () => dispatch(applicationActions.openPage('cron_tasks_list')),
  },
];
