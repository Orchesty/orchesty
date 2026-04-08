---
title: Users Management
helpId: users/overview
order: 1
---

# Users Management

The Users section allows administrators to manage user accounts, assign roles and organize users into access groups.

## Roles

Every user has exactly one **role**. Roles are predefined system presets that determine what sections and actions the user can access. Roles are hierarchical — each higher role includes all permissions from the levels below.

### Available roles

| Role | Access |
|------|--------|
| **Monitoring** | Read-only access to Control Center, Topologies, Scheduled Tasks, Failed Messages and Logs. |
| **Process Management** | Everything in Monitoring, plus the ability to edit scheduled tasks, enable/disable topologies and trigger topology runs. |
| **Developer** | Everything in Process Management, plus full access to topology editing (create, modify, delete) and application management. |
| **System Manager** | Everything in Developer, plus access to the Settings section (API tokens, SDKs, configuration). |
| **Super Admin** | Full access to the entire system, including user management and group administration. |

Only users with the **Super Admin** role can access the Users section and manage other users.

### Changing a user's role

To change an existing user's role, click on their email or the edit icon in the Users grid. In the edit dialog, select a new role from the **Role** dropdown and click **Save**.

## Access Groups

While roles control access to application sections, **access groups** provide fine-grained permissions for specific resources such as individual topologies.

Access groups are custom groups you create to organize per-resource permissions. A user can belong to multiple access groups simultaneously.

### Creating an access group

1. Navigate to the **Groups** tab in the Users section.
2. Click **Create group**.
3. Enter a name for the group.

### Assigning users to groups

Users can be assigned to access groups either during invitation or later through the edit dialog:

- In the **Invite user** or **Edit user** dialog, toggle the desired access groups below the role selector.
- Groups with a checkmark are assigned; click again to remove.

## Inviting Users

To invite a new user to the system:

1. Click the **+ Invite user** button in the top-right corner of the Users page.
2. Enter the new user's **email address**.
3. Select a **Role** from the dropdown (defaults to Monitoring).
4. Optionally assign the user to one or more **Access groups** by clicking on the group badges.
5. Click **Generate invite link**.

The system will either:

- **Send an invitation email** — a link is generated and displayed. You can copy it and share it directly with the user.
- **Restore access** — if the user previously had an account, their access will be re-activated immediately.

## Editing Users

Click on a user's email or the edit icon in the Users grid to open the edit dialog.

From the edit dialog you can:

- **Change the user's role** by selecting a different option from the Role dropdown.
- **Add or remove access groups** by toggling the group badges.
- **Remove the user** by clicking the "Remove user" button. A confirmation dialog will appear before the action is executed. This action cannot be undone.

Changes take effect only after clicking **Save**. The Save button remains disabled until you make a change.

## Topology Access

Access groups gain their permissions through the **topology access management**. Each topology has its own access configuration accessible via the **Access** button on the topology detail page.

### Managing topology access

1. Open a topology and click the **Access** button in the header.
2. The Access drawer shows all groups currently assigned to this topology.
3. Use the **Add group** dropdown to assign a new access group.
4. For each group, toggle the permissions:
   - **Read** — view the topology details
   - **Edit** — modify the topology configuration
   - **Delete** — remove the topology
   - **Run** — execute the topology
5. Click **Save** to apply changes, or **Discard** to revert.

To remove a group's access to a topology entirely, click the remove button next to the group name.
