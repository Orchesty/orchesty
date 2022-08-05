export type Maybe<T> = T | null;
export type InputMaybe<T> = Maybe<T>;
export type Exact<T extends { [key: string]: unknown }> = {
  [K in keyof T]: T[K];
};
export type MakeOptional<T, K extends keyof T> = Omit<T, K> & {
  [SubKey in K]?: Maybe<T[SubKey]>;
};
export type MakeMaybe<T, K extends keyof T> = Omit<T, K> & {
  [SubKey in K]: Maybe<T[SubKey]>;
};
/** All built-in and custom scalars, mapped to their actual values */
export type Scalars = {
  ID: string;
  String: string;
  Boolean: boolean;
  Int: number;
  Float: number;
  /** The javascript `Date` as string. Type represents date and time as the ISO Date string. */
  DateTime: any;
};

export type Admin = {
  __typename?: "Admin";
  firstname: Scalars["String"];
  id: Scalars["Int"];
  isSuperAdmin: Scalars["Boolean"];
  surname: Scalars["String"];
  username: Scalars["String"];
};

export type AdminDashboard = {
  __typename?: "AdminDashboard";
  conflicts: Scalars["Int"];
  laborers: Scalars["Int"];
  pinnedTickets: Scalars["Int"];
  tickets: Scalars["Int"];
  urgentTickets: Scalars["Int"];
};

export type Admins = {
  __typename?: "Admins";
  filter: Array<AdminsFilterParent>;
  items: Array<Admin>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<AdminsSorter>;
};

export type AdminsFilter = {
  __typename?: "AdminsFilter";
  column: AdminsFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum AdminsFilterEnum {
  Firstname = "FIRSTNAME",
  Id = "ID",
  Issuperadmin = "ISSUPERADMIN",
  Surname = "SURNAME",
  Username = "USERNAME",
}

export type AdminsFilterInput = {
  column: AdminsFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type AdminsFilterParent = {
  __typename?: "AdminsFilterParent";
  filter: Array<AdminsFilter>;
};

export type AdminsFilterParentInput = {
  filter: Array<AdminsFilterInput>;
};

export type AdminsInput = {
  filter?: InputMaybe<Array<AdminsFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<AdminsSorterInput>>;
};

export type AdminsSorter = {
  __typename?: "AdminsSorter";
  column: AdminsSorterEnum;
  direction: SorterDirectionEnum;
};

export enum AdminsSorterEnum {
  Firstname = "FIRSTNAME",
  Id = "ID",
  Issuperadmin = "ISSUPERADMIN",
  Surname = "SURNAME",
  Username = "USERNAME",
}

export type AdminsSorterInput = {
  column: AdminsSorterEnum;
  direction: SorterDirectionEnum;
};

export type AssignLaborerDashboardInput = {
  laborerId: Scalars["Int"];
  x: Scalars["Int"];
  y: Scalars["Int"];
};

export type Calendar = {
  __typename?: "Calendar";
  conflicts: Scalars["Int"];
  date: Scalars["DateTime"];
  laborerIds: Array<Scalars["Int"]>;
  laborers: Array<Laborer>;
};

export type CalendarInput = {
  from: Scalars["DateTime"];
  to: Scalars["DateTime"];
};

export type ChangeDeadlineInput = {
  deadLine: Scalars["DateTime"];
  ticketId: Scalars["Int"];
};

export type Chat = {
  __typename?: "Chat";
  id: Scalars["String"];
  isRead: Scalars["Boolean"];
  message: Scalars["String"];
  recipient?: Maybe<Recipient>;
  sender?: Maybe<Sender>;
  sent: Scalars["DateTime"];
  thumbnail?: Maybe<Thumbnail>;
};

export type CompleteMaintenanceInput = {
  maintainedBy: Scalars["String"];
  maintenanceDate: Scalars["DateTime"];
  price: Scalars["Int"];
  report: Scalars["String"];
  type: MaintenanceTypeEnum;
};

export type CreateAdminInput = {
  username: Scalars["String"];
};

export type CreateLaborerEventInput = {
  from: Scalars["DateTime"];
  isHalfDay: Scalars["Boolean"];
  laborerId: Scalars["Int"];
  to: Scalars["DateTime"];
  type: LaborerEventTypeEnum;
};

export type CreateMaintenanceInput = {
  description: Scalars["String"];
  deviceId: Scalars["Int"];
  plannedDate: Scalars["DateTime"];
  responsiblePersonId: Scalars["Int"];
  type: MaintenanceTypeEnum;
};

export type CreateOperationTemplateInput = {
  comfortTime: Scalars["Float"];
  comfortTimeTwo: Scalars["Float"];
  minimumTime: Scalars["Float"];
  minimumTimeTwo: Scalars["Float"];
  name: Scalars["String"];
  value: Scalars["Float"];
  valueTwo: Scalars["Float"];
};

export type CreateProcessSubCodeInput = {
  code: Scalars["String"];
  isCadCam: Scalars["Boolean"];
  name: Scalars["String"];
};

export type CreateProcessTemplateInput = {
  name: Scalars["String"];
  order: Scalars["Float"];
};

export type CreateRegularMaintenanceInput = {
  deviceId: Scalars["Int"];
  frequency: RegularMaintenanceFrequencyEnum;
  name: Scalars["String"];
  number: Scalars["String"];
};

export type DeleteLaborerEventInput = {
  ids: Array<IdInput>;
};

export type Device = {
  __typename?: "Device";
  id: Scalars["Int"];
  laborer: Laborer;
  laborerId: Scalars["Int"];
  name: Scalars["String"];
  note: Scalars["String"];
  number: Scalars["String"];
};

export type DeviceInput = {
  laborerId: Scalars["Int"];
  name: Scalars["String"];
  note: Scalars["String"];
  number: Scalars["String"];
};

export type Devices = {
  __typename?: "Devices";
  filter: Array<DevicesFilterParent>;
  items: Array<Device>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<DevicesSorter>;
};

export type DevicesFilter = {
  __typename?: "DevicesFilter";
  column: DevicesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum DevicesFilterEnum {
  Id = "ID",
  Laborer = "LABORER",
  Name = "NAME",
  Note = "NOTE",
  Number = "NUMBER",
}

export type DevicesFilterInput = {
  column: DevicesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type DevicesFilterParent = {
  __typename?: "DevicesFilterParent";
  filter: Array<DevicesFilter>;
};

export type DevicesFilterParentInput = {
  filter: Array<DevicesFilterInput>;
};

export type DevicesInput = {
  filter?: InputMaybe<Array<DevicesFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<DevicesSorterInput>>;
};

export type DevicesSorter = {
  __typename?: "DevicesSorter";
  column: DevicesSorterEnum;
  direction: SorterDirectionEnum;
};

export enum DevicesSorterEnum {
  Id = "ID",
  Laborer = "LABORER",
  Name = "NAME",
  Note = "NOTE",
  Number = "NUMBER",
}

export type DevicesSorterInput = {
  column: DevicesSorterEnum;
  direction: SorterDirectionEnum;
};

export type Doctor = {
  __typename?: "Doctor";
  city: Scalars["String"];
  companyName: Scalars["String"];
  email: Scalars["String"];
  fullName: Scalars["String"];
  id: Scalars["Int"];
  nameAddress?: Maybe<Scalars["String"]>;
  phone: Scalars["String"];
  postCode: Scalars["String"];
  street: Scalars["String"];
};

export type ExportLaborerEventsInput = {
  from: Scalars["DateTime"];
  laborerId: Scalars["Int"];
  to: Scalars["DateTime"];
};

export enum FilterOperatorEnum {
  Between = "BETWEEN",
  Empty = "EMPTY",
  End = "END",
  Equal = "EQUAL",
  GreaterThan = "GREATER_THAN",
  GreaterThanOrEqual = "GREATER_THAN_OR_EQUAL",
  In = "IN",
  Like = "LIKE",
  LowerThan = "LOWER_THAN",
  LowerThanOrEqual = "LOWER_THAN_OR_EQUAL",
  NotBetween = "NOT_BETWEEN",
  NotEmpty = "NOT_EMPTY",
  NotEqual = "NOT_EQUAL",
  NotIn = "NOT_IN",
  Start = "START",
}

export type Id = {
  __typename?: "Id";
  id: Scalars["String"];
};

export type IdInput = {
  id: Scalars["String"];
};

export type InviteLaborerInput = {
  username: Scalars["String"];
};

export type Laborer = {
  __typename?: "Laborer";
  firstname: Scalars["String"];
  id: Scalars["Int"];
  isCadCam: Scalars["Boolean"];
  state: LaborerStateEnum;
  status: LaborerStatusEnum;
  surname: Scalars["String"];
  ticketLimit: Scalars["Int"];
  username: Scalars["String"];
};

export type LaborerEvent = {
  __typename?: "LaborerEvent";
  date: Scalars["DateTime"];
  fromDate: Scalars["DateTime"];
  id: Scalars["Int"];
  isHalfDay: Scalars["Boolean"];
  laborer: Laborer;
  laborerId: Scalars["Int"];
  toDate: Scalars["DateTime"];
  type: LaborerEventTypeEnum;
};

export enum LaborerEventTypeEnum {
  Sickness = "sickness",
  Vacation = "vacation",
}

export type LaborerEvents = {
  __typename?: "LaborerEvents";
  filter: Array<LaborerEventsFilterParent>;
  items: Array<LaborerEventsCustom>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<LaborerEventsSorter>;
};

export type LaborerEventsCustom = {
  __typename?: "LaborerEventsCustom";
  fromDate: Scalars["DateTime"];
  hours: Scalars["Int"];
  ids: Array<Id>;
  isHalfDay: Scalars["Boolean"];
  laborerId: Scalars["Int"];
  toDate: Scalars["DateTime"];
  type: LaborerEventTypeEnum;
};

export type LaborerEventsFilter = {
  __typename?: "LaborerEventsFilter";
  column: LaborerEventsFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum LaborerEventsFilterEnum {
  Fromdate = "FROMDATE",
  Hours = "HOURS",
  Id = "ID",
  Laborer = "LABORER",
  Todate = "TODATE",
  Type = "TYPE",
}

export type LaborerEventsFilterInput = {
  column: LaborerEventsFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type LaborerEventsFilterParent = {
  __typename?: "LaborerEventsFilterParent";
  filter: Array<LaborerEventsFilter>;
};

export type LaborerEventsFilterParentInput = {
  filter: Array<LaborerEventsFilterInput>;
};

export type LaborerEventsGroup = {
  __typename?: "LaborerEventsGroup";
  fromDate: Scalars["DateTime"];
  ids: Array<Id>;
  isHalfDay: Scalars["Boolean"];
  laborerId: Scalars["Int"];
  toDate: Scalars["DateTime"];
  type: LaborerEventTypeEnum;
};

export type LaborerEventsInput = {
  filter?: InputMaybe<Array<LaborerEventsFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<LaborerEventsSorterInput>>;
};

export type LaborerEventsSorter = {
  __typename?: "LaborerEventsSorter";
  column: LaborerEventsSorterEnum;
  direction: SorterDirectionEnum;
};

export enum LaborerEventsSorterEnum {
  Fromdate = "FROMDATE",
  Hours = "HOURS",
  Id = "ID",
  Laborer = "LABORER",
  Todate = "TODATE",
  Type = "TYPE",
}

export type LaborerEventsSorterInput = {
  column: LaborerEventsSorterEnum;
  direction: SorterDirectionEnum;
};

export enum LaborerStateEnum {
  Offline = "offline",
  Online = "online",
}

export enum LaborerStatusEnum {
  Active = "active",
  Deleted = "deleted",
  Inactive = "inactive",
}

export type Laborers = {
  __typename?: "Laborers";
  filter: Array<LaborersFilterParent>;
  items: Array<Laborer>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<LaborersSorter>;
};

export type LaborersDashboard = {
  __typename?: "LaborersDashboard";
  admin: Admin;
  adminId: Scalars["Int"];
  hasConflict: Scalars["Boolean"];
  laborer: Laborer;
  laborerId: Scalars["Int"];
  tickets: Scalars["Int"];
  urgentTickets: Scalars["Int"];
  x: Scalars["Int"];
  y: Scalars["Int"];
};

export type LaborersDashboardInput = {
  date: Scalars["DateTime"];
};

export type LaborersFilter = {
  __typename?: "LaborersFilter";
  column: LaborersFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum LaborersFilterEnum {
  Firstname = "FIRSTNAME",
  Id = "ID",
  IsCadCam = "IS_CAD_CAM",
  Status = "STATUS",
  Surname = "SURNAME",
}

export type LaborersFilterInput = {
  column: LaborersFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type LaborersFilterParent = {
  __typename?: "LaborersFilterParent";
  filter: Array<LaborersFilter>;
};

export type LaborersFilterParentInput = {
  filter: Array<LaborersFilterInput>;
};

export type LaborersInput = {
  filter?: InputMaybe<Array<LaborersFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<LaborersSorterInput>>;
};

export type LaborersSorter = {
  __typename?: "LaborersSorter";
  column: LaborersSorterEnum;
  direction: SorterDirectionEnum;
};

export enum LaborersSorterEnum {
  Firstname = "FIRSTNAME",
  Id = "ID",
  IsCadCam = "IS_CAD_CAM",
  Status = "STATUS",
  Surname = "SURNAME",
}

export type LaborersSorterInput = {
  column: LaborersSorterEnum;
  direction: SorterDirectionEnum;
};

export type Macro = {
  __typename?: "Macro";
  items: Scalars["Int"];
  name: Scalars["String"];
  price: Scalars["Float"];
  subCode: Scalars["String"];
};

export type Maintenance = {
  __typename?: "Maintenance";
  description: Scalars["String"];
  device: Device;
  deviceId: Scalars["Int"];
  id: Scalars["Int"];
  maintainedBy?: Maybe<Scalars["String"]>;
  maintenanceDate?: Maybe<Scalars["DateTime"]>;
  plannedDate: Scalars["DateTime"];
  price?: Maybe<Scalars["Int"]>;
  report?: Maybe<Scalars["String"]>;
  responsiblePerson: Admin;
  responsiblePersonId: Scalars["Int"];
  state: MaintenanceStateEnum;
  type: MaintenanceTypeEnum;
};

export enum MaintenanceStateEnum {
  Done = "done",
  Planned = "planned",
}

export enum MaintenanceTypeEnum {
  Fault = "fault",
  RegularMaintenance = "regularMaintenance",
}

export type Maintenances = {
  __typename?: "Maintenances";
  filter: Array<MaintenancesFilterParent>;
  items: Array<Maintenance>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<MaintenancesSorter>;
};

export type MaintenancesFilter = {
  __typename?: "MaintenancesFilter";
  column: MaintenancesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum MaintenancesFilterEnum {
  Description = "DESCRIPTION",
  Device = "DEVICE",
  Id = "ID",
  Maintainedby = "MAINTAINEDBY",
  Maintenancedate = "MAINTENANCEDATE",
  PlannedDate = "PLANNED_DATE",
  Price = "PRICE",
  Report = "REPORT",
  Responsibleperson = "RESPONSIBLEPERSON",
  State = "STATE",
  Type = "TYPE",
}

export type MaintenancesFilterInput = {
  column: MaintenancesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type MaintenancesFilterParent = {
  __typename?: "MaintenancesFilterParent";
  filter: Array<MaintenancesFilter>;
};

export type MaintenancesFilterParentInput = {
  filter: Array<MaintenancesFilterInput>;
};

export type MaintenancesInput = {
  filter?: InputMaybe<Array<MaintenancesFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<MaintenancesSorterInput>>;
};

export type MaintenancesSorter = {
  __typename?: "MaintenancesSorter";
  column: MaintenancesSorterEnum;
  direction: SorterDirectionEnum;
};

export enum MaintenancesSorterEnum {
  Description = "DESCRIPTION",
  Device = "DEVICE",
  Id = "ID",
  Maintainedby = "MAINTAINEDBY",
  Maintenancedate = "MAINTENANCEDATE",
  PlannedDate = "PLANNED_DATE",
  Price = "PRICE",
  Report = "REPORT",
  Responsibleperson = "RESPONSIBLEPERSON",
  State = "STATE",
  Type = "TYPE",
}

export type MaintenancesSorterInput = {
  column: MaintenancesSorterEnum;
  direction: SorterDirectionEnum;
};

export type Mutation = {
  __typename?: "Mutation";
  assignLaborerDashboard: Scalars["Boolean"];
  changeDeadline: Scalars["Boolean"];
  completeMaintenance: Maintenance;
  copyDevice: Device;
  createAdmin: Admin;
  createDevice: Device;
  createLaborerEvent: Array<LaborerEvent>;
  createMaintenance: Maintenance;
  createOperationTemplate: OperationTemplate;
  createProcessSubCode: ProcessSubCode;
  createProcessTemplate: ProcessTemplate;
  createRegularMaintenance: RegularMaintenance;
  deleteAdmin: Scalars["Boolean"];
  deleteDevice: Scalars["Boolean"];
  deleteLaborerEvent: Scalars["Boolean"];
  deleteMaintenance: Scalars["Boolean"];
  deleteOperationTemplate: Scalars["Boolean"];
  deleteProcessSubCode: Scalars["Boolean"];
  deleteProcessTemplate: Scalars["Boolean"];
  deleteRegularMaintenance: Scalars["Boolean"];
  inviteLaborer: Scalars["Boolean"];
  makeTicketImportant: Scalars["Boolean"];
  makeTicketNotImportant: Scalars["Boolean"];
  makeTicketProblem: Scalars["Boolean"];
  pinTicket: Scalars["Boolean"];
  solveTicketProblem: Scalars["Boolean"];
  syncLaborers: Scalars["Boolean"];
  unpinTicket: Scalars["Boolean"];
  updateAdmin: Admin;
  updateDevice: Device;
  updateLaborer: Laborer;
  updateLaborerEvent: LaborerEventsGroup;
  updateLoggedAdmin: Admin;
  updateLoggedAdminPassword: Scalars["Boolean"];
  updateMaintenance: Maintenance;
  updateNotification?: Maybe<Notification>;
  updateOperation: Operation;
  updateOperationTemplate: OperationTemplate;
  updateProcess: Process;
  updateProcessCode: ProcessCode;
  updateProcessSubCode: ProcessSubCode;
  updateProcessTemplate: ProcessTemplate;
  updateRegularMaintenance: RegularMaintenance;
};

export type MutationAssignLaborerDashboardArgs = {
  input: AssignLaborerDashboardInput;
};

export type MutationChangeDeadlineArgs = {
  input: ChangeDeadlineInput;
};

export type MutationCompleteMaintenanceArgs = {
  id: Scalars["Int"];
  input: CompleteMaintenanceInput;
};

export type MutationCopyDeviceArgs = {
  id: Scalars["Int"];
};

export type MutationCreateAdminArgs = {
  input: CreateAdminInput;
};

export type MutationCreateDeviceArgs = {
  input: DeviceInput;
};

export type MutationCreateLaborerEventArgs = {
  input: CreateLaborerEventInput;
};

export type MutationCreateMaintenanceArgs = {
  input: CreateMaintenanceInput;
};

export type MutationCreateOperationTemplateArgs = {
  input: CreateOperationTemplateInput;
};

export type MutationCreateProcessSubCodeArgs = {
  input: CreateProcessSubCodeInput;
};

export type MutationCreateProcessTemplateArgs = {
  input: CreateProcessTemplateInput;
};

export type MutationCreateRegularMaintenanceArgs = {
  input: CreateRegularMaintenanceInput;
};

export type MutationDeleteAdminArgs = {
  id: Scalars["Int"];
};

export type MutationDeleteDeviceArgs = {
  id: Scalars["Int"];
};

export type MutationDeleteLaborerEventArgs = {
  input: DeleteLaborerEventInput;
};

export type MutationDeleteMaintenanceArgs = {
  id: Scalars["Int"];
};

export type MutationDeleteOperationTemplateArgs = {
  id: Scalars["Int"];
};

export type MutationDeleteProcessSubCodeArgs = {
  id: Scalars["Int"];
};

export type MutationDeleteProcessTemplateArgs = {
  id: Scalars["Int"];
};

export type MutationDeleteRegularMaintenanceArgs = {
  id: Scalars["Int"];
};

export type MutationInviteLaborerArgs = {
  id: Scalars["Int"];
  input: InviteLaborerInput;
};

export type MutationMakeTicketImportantArgs = {
  id: Scalars["Int"];
};

export type MutationMakeTicketNotImportantArgs = {
  id: Scalars["Int"];
};

export type MutationMakeTicketProblemArgs = {
  id: Scalars["Int"];
};

export type MutationPinTicketArgs = {
  id: Scalars["Int"];
};

export type MutationSolveTicketProblemArgs = {
  id: Scalars["Int"];
};

export type MutationUnpinTicketArgs = {
  id: Scalars["Int"];
};

export type MutationUpdateAdminArgs = {
  id: Scalars["Int"];
  input: UpdateAdminInput;
};

export type MutationUpdateDeviceArgs = {
  id: Scalars["Int"];
  input: DeviceInput;
};

export type MutationUpdateLaborerArgs = {
  id: Scalars["Int"];
  input: UpdateLaborerInput;
};

export type MutationUpdateLaborerEventArgs = {
  input: UpdateLaborerEventInput;
};

export type MutationUpdateLoggedAdminArgs = {
  input: UpdateLoggedAdminInput;
};

export type MutationUpdateLoggedAdminPasswordArgs = {
  input: UpdateLoggedAdminPasswordInput;
};

export type MutationUpdateMaintenanceArgs = {
  id: Scalars["Int"];
  input: UpdateMaintenanceInput;
};

export type MutationUpdateNotificationArgs = {
  id: Scalars["Int"];
};

export type MutationUpdateOperationArgs = {
  id: Scalars["Int"];
  input: UpdateOperationInput;
};

export type MutationUpdateOperationTemplateArgs = {
  id: Scalars["Float"];
  input: UpdateOperationTemplateInput;
};

export type MutationUpdateProcessArgs = {
  id: Scalars["Int"];
  input: UpdateProcessInput;
};

export type MutationUpdateProcessCodeArgs = {
  code: Scalars["String"];
  input: ProcessCodeInput;
};

export type MutationUpdateProcessSubCodeArgs = {
  id: Scalars["Int"];
  input: UpdateProcessSubCodeInput;
};

export type MutationUpdateProcessTemplateArgs = {
  id: Scalars["Int"];
  input: UpdateProcessTemplateInput;
};

export type MutationUpdateRegularMaintenanceArgs = {
  id: Scalars["Int"];
  input: UpdateRegularMaintenanceInput;
};

export type Notification = {
  __typename?: "Notification";
  admin?: Maybe<Admin>;
  adminId?: Maybe<Scalars["Int"]>;
  content: Scalars["String"];
  created: Scalars["DateTime"];
  data?: Maybe<Scalars["String"]>;
  laborer?: Maybe<Laborer>;
  laborerId?: Maybe<Scalars["Int"]>;
  objectType: NotificationType;
  title: Scalars["String"];
};

export enum NotificationFilterEnum {
  AdminId = "ADMIN_ID",
  Created = "CREATED",
  Id = "ID",
  LaborerId = "LABORER_ID",
  Title = "TITLE",
  Type = "TYPE",
  Updated = "UPDATED",
}

export enum NotificationSorterEnum {
  AdminId = "ADMIN_ID",
  Created = "CREATED",
  Id = "ID",
  LaborerId = "LABORER_ID",
  Title = "TITLE",
  Type = "TYPE",
  Updated = "UPDATED",
}

export enum NotificationType {
  CreateProcessError = "CREATE_PROCESS_ERROR",
  SyncedNewTickets = "SYNCED_NEW_TICKETS",
  TicketHandover = "TICKET_HANDOVER",
  TicketInvalidDate = "TICKET_INVALID_DATE",
  TicketMarkedAsImportant = "TICKET_MARKED_AS_IMPORTANT",
  TicketMarkedAsNotImportant = "TICKET_MARKED_AS_NOT_IMPORTANT",
  TicketMarkedAsProblem = "TICKET_MARKED_AS_PROBLEM",
  TicketMarkedAsUrgent = "TICKET_MARKED_AS_URGENT",
  TicketReturned = "TICKET_RETURNED",
}

export type Notifications = {
  __typename?: "Notifications";
  filter: Array<NotificationsFilterParent>;
  items: Array<Notification>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<NotificationsSorter>;
};

export type NotificationsFilter = {
  __typename?: "NotificationsFilter";
  column: NotificationFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type NotificationsFilterInput = {
  column: NotificationFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type NotificationsFilterParent = {
  __typename?: "NotificationsFilterParent";
  filter: Array<NotificationsFilter>;
};

export type NotificationsFilterParentInput = {
  filter: Array<NotificationsFilterInput>;
};

export type NotificationsInput = {
  filter?: InputMaybe<Array<NotificationsFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<NotificationsSorterInput>>;
};

export type NotificationsSorter = {
  __typename?: "NotificationsSorter";
  column: NotificationSorterEnum;
  direction: SorterDirectionEnum;
};

export type NotificationsSorterInput = {
  column: NotificationSorterEnum;
  direction: SorterDirectionEnum;
};

export type Operation = {
  __typename?: "Operation";
  conflict: Scalars["Boolean"];
  estimateFrom: Scalars["DateTime"];
  estimateTo: Scalars["DateTime"];
  id: Scalars["Int"];
  laborer: Laborer;
  laborerId: Scalars["Int"];
  message?: Maybe<Scalars["String"]>;
  price: Scalars["Float"];
  realFrom?: Maybe<Scalars["DateTime"]>;
  realTo?: Maybe<Scalars["DateTime"]>;
  skipped: Scalars["Boolean"];
  status: OperationStatusEnum;
  template: OperationTemplate;
  templateId: Scalars["Int"];
};

export enum OperationStatusEnum {
  Done = "done",
  Handover = "handover",
  InProgress = "inProgress",
  Returned = "returned",
  Todo = "todo",
}

export type OperationTemplate = {
  __typename?: "OperationTemplate";
  comfortTime: Scalars["Float"];
  comfortTimeTwo: Scalars["Float"];
  id: Scalars["Int"];
  minimumTime: Scalars["Float"];
  minimumTimeTwo: Scalars["Float"];
  name: Scalars["String"];
  processSubCodeIds: Array<Scalars["Int"]>;
  processSubCodes: Array<ProcessSubCode>;
  value: Scalars["Float"];
  valueTwo: Scalars["Float"];
};

export type OperationTemplates = {
  __typename?: "OperationTemplates";
  filter: Array<OperationTemplatesFilterParent>;
  items: Array<OperationTemplate>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<OperationTemplatesSorter>;
};

export type OperationTemplatesFilter = {
  __typename?: "OperationTemplatesFilter";
  column: OperationTemplatesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum OperationTemplatesFilterEnum {
  Comforttime = "COMFORTTIME",
  Comforttimetwo = "COMFORTTIMETWO",
  Id = "ID",
  Minimumtime = "MINIMUMTIME",
  Minimumtimetwo = "MINIMUMTIMETWO",
  Name = "NAME",
  Value = "VALUE",
  Valuetwo = "VALUETWO",
}

export type OperationTemplatesFilterInput = {
  column: OperationTemplatesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type OperationTemplatesFilterParent = {
  __typename?: "OperationTemplatesFilterParent";
  filter: Array<OperationTemplatesFilter>;
};

export type OperationTemplatesFilterParentInput = {
  filter: Array<OperationTemplatesFilterInput>;
};

export type OperationTemplatesInput = {
  filter?: InputMaybe<Array<OperationTemplatesFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<OperationTemplatesSorterInput>>;
};

export type OperationTemplatesSorter = {
  __typename?: "OperationTemplatesSorter";
  column: OperationTemplatesSorterEnum;
  direction: SorterDirectionEnum;
};

export enum OperationTemplatesSorterEnum {
  Comforttime = "COMFORTTIME",
  Comforttimetwo = "COMFORTTIMETWO",
  Id = "ID",
  Minimumtime = "MINIMUMTIME",
  Minimumtimetwo = "MINIMUMTIMETWO",
  Name = "NAME",
  Value = "VALUE",
  Valuetwo = "VALUETWO",
}

export type OperationTemplatesSorterInput = {
  column: OperationTemplatesSorterEnum;
  direction: SorterDirectionEnum;
};

export type Pager = {
  __typename?: "Pager";
  last: Scalars["Int"];
  next: Scalars["Int"];
  page: Scalars["Int"];
  prev: Scalars["Int"];
  size: Scalars["Int"];
  total: Scalars["Int"];
};

export type PagerInput = {
  page: Scalars["Int"];
  size: Scalars["Int"];
};

export type Patient = {
  __typename?: "Patient";
  allergies?: Maybe<Scalars["String"]>;
  city?: Maybe<Scalars["String"]>;
  email?: Maybe<Scalars["String"]>;
  firstname: Scalars["String"];
  id: Scalars["Int"];
  nationalId: Scalars["String"];
  note?: Maybe<Scalars["String"]>;
  phone?: Maybe<Scalars["String"]>;
  street?: Maybe<Scalars["String"]>;
  surname: Scalars["String"];
  zipCode?: Maybe<Scalars["String"]>;
};

export type Process = {
  __typename?: "Process";
  deadline: Scalars["DateTime"];
  id: Scalars["Int"];
  operations: Array<Operation>;
  operationsIds: Array<Scalars["Int"]>;
};

export type ProcessCode = {
  __typename?: "ProcessCode";
  code: Scalars["String"];
  processTemplate?: Maybe<ProcessTemplate>;
};

export type ProcessCodeInput = {
  processTemplateId: Scalars["Int"];
};

export type ProcessCodes = {
  __typename?: "ProcessCodes";
  filter: Array<ProcessCodesFilterParent>;
  items: Array<ProcessCode>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<ProcessCodesSorter>;
};

export type ProcessCodesFilter = {
  __typename?: "ProcessCodesFilter";
  column: ProcessCodesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type ProcessCodesFilterInput = {
  column: ProcessCodesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type ProcessCodesFilterParent = {
  __typename?: "ProcessCodesFilterParent";
  filter: Array<ProcessCodesFilter>;
};

export type ProcessCodesFilterParentInput = {
  filter: Array<ProcessCodesFilterInput>;
};

export type ProcessCodesInput = {
  filter?: InputMaybe<Array<ProcessCodesFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<ProcessCodesSorterInput>>;
};

export type ProcessCodesSorter = {
  __typename?: "ProcessCodesSorter";
  column: ProcessCodesSorterEnum;
  direction: SorterDirectionEnum;
};

export type ProcessCodesSorterInput = {
  column: ProcessCodesSorterEnum;
  direction: SorterDirectionEnum;
};

export type ProcessSubCode = {
  __typename?: "ProcessSubCode";
  code: Scalars["String"];
  id: Scalars["Float"];
  isCadCam: Scalars["Boolean"];
  name: Scalars["String"];
  operationTemplates: Array<OperationTemplate>;
  processTemplates: Array<ProcessTemplate>;
};

export type ProcessSubCodes = {
  __typename?: "ProcessSubCodes";
  filter: Array<ProcessSubCodesFilterParent>;
  items: Array<ProcessSubCode>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<ProcessSubCodesSorter>;
};

export type ProcessSubCodesFilter = {
  __typename?: "ProcessSubCodesFilter";
  column: ProcessSubCodesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum ProcessSubCodesFilterEnum {
  Code = "CODE",
  Id = "ID",
  IsCadCam = "IS_CAD_CAM",
  Name = "NAME",
}

export type ProcessSubCodesFilterInput = {
  column: ProcessSubCodesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type ProcessSubCodesFilterParent = {
  __typename?: "ProcessSubCodesFilterParent";
  filter: Array<ProcessSubCodesFilter>;
};

export type ProcessSubCodesFilterParentInput = {
  filter: Array<ProcessSubCodesFilterInput>;
};

export type ProcessSubCodesInput = {
  filter?: InputMaybe<Array<ProcessSubCodesFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<ProcessSubCodesSorterInput>>;
};

export type ProcessSubCodesSorter = {
  __typename?: "ProcessSubCodesSorter";
  column: ProcessSubCodesSorterEnum;
  direction: SorterDirectionEnum;
};

export enum ProcessSubCodesSorterEnum {
  Code = "CODE",
  Id = "ID",
  IsCadCam = "IS_CAD_CAM",
  Name = "NAME",
}

export type ProcessSubCodesSorterInput = {
  column: ProcessSubCodesSorterEnum;
  direction: SorterDirectionEnum;
};

export type ProcessTemplate = {
  __typename?: "ProcessTemplate";
  id: Scalars["Int"];
  name: Scalars["String"];
  order: Scalars["Float"];
  processSubCodeIds: Array<Scalars["Int"]>;
  processSubCodes: Array<ProcessSubCode>;
};

export type ProcessTemplates = {
  __typename?: "ProcessTemplates";
  filter: Array<ProcessTemplatesFilterParent>;
  items: Array<ProcessTemplate>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<ProcessTemplatesSorter>;
};

export type ProcessTemplatesFilter = {
  __typename?: "ProcessTemplatesFilter";
  column: ProcessTemplatesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum ProcessTemplatesFilterEnum {
  Id = "ID",
  Name = "NAME",
  Order = "ORDER",
}

export type ProcessTemplatesFilterInput = {
  column: ProcessTemplatesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type ProcessTemplatesFilterParent = {
  __typename?: "ProcessTemplatesFilterParent";
  filter: Array<ProcessTemplatesFilter>;
};

export type ProcessTemplatesFilterParentInput = {
  filter: Array<ProcessTemplatesFilterInput>;
};

export type ProcessTemplatesInput = {
  filter?: InputMaybe<Array<ProcessTemplatesFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<ProcessTemplatesSorterInput>>;
};

export type ProcessTemplatesSorter = {
  __typename?: "ProcessTemplatesSorter";
  column: ProcessTemplatesSorterEnum;
  direction: SorterDirectionEnum;
};

export enum ProcessTemplatesSorterEnum {
  Id = "ID",
  Name = "NAME",
  Order = "ORDER",
}

export type ProcessTemplatesSorterInput = {
  column: ProcessTemplatesSorterEnum;
  direction: SorterDirectionEnum;
};

export type Query = {
  __typename?: "Query";
  admin: Admin;
  admins: Admins;
  calendar: Array<Calendar>;
  dashboard: AdminDashboard;
  device: Device;
  devices: Devices;
  exportLaborerEvents: Scalars["String"];
  laborer: Laborer;
  laborerEvent: LaborerEvent;
  laborerEvents: LaborerEvents;
  laborers: Laborers;
  laborersDashboard: Array<LaborersDashboard>;
  loggedAdmin: Admin;
  maintenance: Maintenance;
  maintenances: Maintenances;
  notification: Notification;
  notifications: Notifications;
  operationTemplate: OperationTemplate;
  operationTemplates: OperationTemplates;
  pinnedTickets: Tickets;
  processCode: ProcessCode;
  processCodes: ProcessCodes;
  processSubCode: ProcessSubCode;
  processSubCodes: ProcessSubCodes;
  processTemplate: ProcessTemplate;
  processTemplates: ProcessTemplates;
  regularMaintenance: RegularMaintenance;
  regularMaintenances: RegularMaintenances;
  ticket: Ticket;
  tickets: Tickets;
};

export type QueryAdminArgs = {
  id: Scalars["Int"];
};

export type QueryAdminsArgs = {
  input?: InputMaybe<AdminsInput>;
};

export type QueryCalendarArgs = {
  input: CalendarInput;
};

export type QueryDeviceArgs = {
  id: Scalars["Int"];
};

export type QueryDevicesArgs = {
  input?: InputMaybe<DevicesInput>;
};

export type QueryExportLaborerEventsArgs = {
  input: ExportLaborerEventsInput;
};

export type QueryLaborerArgs = {
  id: Scalars["Int"];
};

export type QueryLaborerEventArgs = {
  id: Scalars["Int"];
};

export type QueryLaborerEventsArgs = {
  input?: InputMaybe<LaborerEventsInput>;
};

export type QueryLaborersArgs = {
  input?: InputMaybe<LaborersInput>;
};

export type QueryLaborersDashboardArgs = {
  input: LaborersDashboardInput;
};

export type QueryMaintenanceArgs = {
  id: Scalars["Int"];
};

export type QueryMaintenancesArgs = {
  input?: InputMaybe<MaintenancesInput>;
};

export type QueryNotificationArgs = {
  id: Scalars["Int"];
};

export type QueryNotificationsArgs = {
  input?: InputMaybe<NotificationsInput>;
};

export type QueryOperationTemplateArgs = {
  id: Scalars["Int"];
};

export type QueryOperationTemplatesArgs = {
  input?: InputMaybe<OperationTemplatesInput>;
};

export type QueryPinnedTicketsArgs = {
  input?: InputMaybe<TicketsInput>;
};

export type QueryProcessCodeArgs = {
  code: Scalars["String"];
};

export type QueryProcessCodesArgs = {
  input?: InputMaybe<ProcessCodesInput>;
};

export type QueryProcessSubCodeArgs = {
  id: Scalars["Int"];
};

export type QueryProcessSubCodesArgs = {
  input?: InputMaybe<ProcessSubCodesInput>;
};

export type QueryProcessTemplateArgs = {
  id: Scalars["Int"];
};

export type QueryProcessTemplatesArgs = {
  input?: InputMaybe<ProcessTemplatesInput>;
};

export type QueryRegularMaintenanceArgs = {
  id: Scalars["Int"];
};

export type QueryRegularMaintenancesArgs = {
  input?: InputMaybe<RegularMaintenancesInput>;
};

export type QueryTicketArgs = {
  id: Scalars["Int"];
};

export type QueryTicketsArgs = {
  input?: InputMaybe<TicketsInput>;
};

export type Recipient = {
  __typename?: "Recipient";
  firstname: Scalars["String"];
  surname: Scalars["String"];
};

export type RegularMaintenance = {
  __typename?: "RegularMaintenance";
  device: Device;
  deviceId: Scalars["Int"];
  frequency: RegularMaintenanceFrequencyEnum;
  id: Scalars["Int"];
  name: Scalars["String"];
  number: Scalars["String"];
};

export enum RegularMaintenanceFrequencyEnum {
  Daily = "daily",
  Monthly = "monthly",
  Weekly = "weekly",
  Yearly = "yearly",
}

export type RegularMaintenances = {
  __typename?: "RegularMaintenances";
  filter: Array<RegularMaintenancesFilterParent>;
  items: Array<RegularMaintenance>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<RegularMaintenancesSorter>;
};

export type RegularMaintenancesFilter = {
  __typename?: "RegularMaintenancesFilter";
  column: RegularMaintenancesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export enum RegularMaintenancesFilterEnum {
  DeviceId = "DEVICE_ID",
  DeviceNumber = "DEVICE_NUMBER",
  Frequency = "FREQUENCY",
  Id = "ID",
  Name = "NAME",
  Number = "NUMBER",
}

export type RegularMaintenancesFilterInput = {
  column: RegularMaintenancesFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type RegularMaintenancesFilterParent = {
  __typename?: "RegularMaintenancesFilterParent";
  filter: Array<RegularMaintenancesFilter>;
};

export type RegularMaintenancesFilterParentInput = {
  filter: Array<RegularMaintenancesFilterInput>;
};

export type RegularMaintenancesInput = {
  filter?: InputMaybe<Array<RegularMaintenancesFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<RegularMaintenancesSorterInput>>;
};

export type RegularMaintenancesSorter = {
  __typename?: "RegularMaintenancesSorter";
  column: RegularMaintenancesSorterEnum;
  direction: SorterDirectionEnum;
};

export enum RegularMaintenancesSorterEnum {
  DeviceId = "DEVICE_ID",
  DeviceNumber = "DEVICE_NUMBER",
  Frequency = "FREQUENCY",
  Id = "ID",
  Name = "NAME",
  Number = "NUMBER",
}

export type RegularMaintenancesSorterInput = {
  column: RegularMaintenancesSorterEnum;
  direction: SorterDirectionEnum;
};

export type Sender = {
  __typename?: "Sender";
  firstname: Scalars["String"];
  surname: Scalars["String"];
};

export enum SorterDirectionEnum {
  Ascending = "ASCENDING",
  Descending = "DESCENDING",
}

export type Thumbnail = {
  __typename?: "Thumbnail";
  extension: Scalars["String"];
  id: Scalars["String"];
  isShared: Scalars["Boolean"];
  mimeType: Scalars["String"];
  name: Scalars["String"];
  thumbnailContent?: Maybe<Scalars["String"]>;
};

export type Ticket = {
  __typename?: "Ticket";
  created: Scalars["DateTime"];
  doctor: Doctor;
  doctorId: Scalars["Int"];
  doctorNote: Scalars["String"];
  externalChat: Array<Chat>;
  externalId: Scalars["String"];
  id: Scalars["Int"];
  important: Scalars["Boolean"];
  internalChat: Array<Chat>;
  isPinned: Scalars["Boolean"];
  laboratoryNote?: Maybe<Scalars["String"]>;
  laborer: Laborer;
  laborerId: Scalars["Int"];
  locationName: Scalars["String"];
  macros: Array<Macro>;
  number: Scalars["String"];
  operations: Array<Operation>;
  patient: Patient;
  patientId: Scalars["Int"];
  price?: Maybe<Scalars["Float"]>;
  problem: Scalars["Boolean"];
  process: Process;
  processId: Scalars["Int"];
  thumbnail: Array<Thumbnail>;
  urgent: Scalars["Boolean"];
};

export enum TicketFilterEnum {
  Created = "CREATED",
  Doctor = "DOCTOR",
  Estimatetodate = "ESTIMATETODATE",
  Id = "ID",
  Important = "IMPORTANT",
  Laborer = "LABORER",
  Number = "NUMBER",
  Operationlaborer = "OPERATIONLABORER",
  Patient = "PATIENT",
  Previousoperationstatus = "PREVIOUSOPERATIONSTATUS",
  Price = "PRICE",
  Problem = "PROBLEM",
  Process = "PROCESS",
  Status = "STATUS",
  Urgent = "URGENT",
}

export enum TicketSorterEnum {
  Created = "CREATED",
  Doctor = "DOCTOR",
  Estimatetodate = "ESTIMATETODATE",
  Id = "ID",
  Important = "IMPORTANT",
  Laborer = "LABORER",
  Number = "NUMBER",
  Olaborer = "OLABORER",
  Patient = "PATIENT",
  Postatus = "POSTATUS",
  Price = "PRICE",
  Problem = "PROBLEM",
  Process = "PROCESS",
  Status = "STATUS",
  Urgent = "URGENT",
}

export type Tickets = {
  __typename?: "Tickets";
  filter: Array<TicketsFilterParent>;
  items: Array<Ticket>;
  pager: Pager;
  search: Scalars["String"];
  sorter: Array<TicketsSorter>;
};

export type TicketsFilter = {
  __typename?: "TicketsFilter";
  column: TicketFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type TicketsFilterInput = {
  column: TicketFilterEnum;
  operator: FilterOperatorEnum;
  values: Array<Scalars["String"]>;
};

export type TicketsFilterParent = {
  __typename?: "TicketsFilterParent";
  filter: Array<TicketsFilter>;
};

export type TicketsFilterParentInput = {
  filter: Array<TicketsFilterInput>;
};

export type TicketsInput = {
  filter?: InputMaybe<Array<TicketsFilterParentInput>>;
  pager?: InputMaybe<PagerInput>;
  search?: InputMaybe<Scalars["String"]>;
  sorter?: InputMaybe<Array<TicketsSorterInput>>;
};

export type TicketsSorter = {
  __typename?: "TicketsSorter";
  column: TicketSorterEnum;
  direction: SorterDirectionEnum;
};

export type TicketsSorterInput = {
  column: TicketSorterEnum;
  direction: SorterDirectionEnum;
};

export type UpdateAdminInput = {
  firstname: Scalars["String"];
  isSuperAdmin: Scalars["Boolean"];
  surname: Scalars["String"];
  username: Scalars["String"];
};

export type UpdateLaborerEventInput = {
  fromDate: Scalars["DateTime"];
  ids: Array<IdInput>;
  isHalfDay: Scalars["Boolean"];
  laborerId: Scalars["Int"];
  toDate: Scalars["DateTime"];
  type: LaborerEventTypeEnum;
};

export type UpdateLaborerInput = {
  ticketLimit: Scalars["Int"];
};

export type UpdateLoggedAdminInput = {
  firstname: Scalars["String"];
  surname: Scalars["String"];
  username: Scalars["String"];
};

export type UpdateLoggedAdminPasswordInput = {
  newPasswordOne: Scalars["String"];
  newPasswordTwo: Scalars["String"];
  oldPassword: Scalars["String"];
};

export type UpdateMaintenanceInput = {
  description: Scalars["String"];
  deviceId: Scalars["Int"];
  plannedDate: Scalars["DateTime"];
  responsiblePersonId: Scalars["Int"];
  type: MaintenanceTypeEnum;
};

export type UpdateOperationInput = {
  estimateTo: Scalars["DateTime"];
};

export type UpdateOperationTemplateInput = {
  comfortTime: Scalars["Float"];
  comfortTimeTwo: Scalars["Float"];
  minimumTime: Scalars["Float"];
  minimumTimeTwo: Scalars["Float"];
  name: Scalars["String"];
  value: Scalars["Float"];
  valueTwo: Scalars["Float"];
};

export type UpdateProcessInput = {
  deadline: Scalars["DateTime"];
};

export type UpdateProcessSubCodeInput = {
  code: Scalars["String"];
  isCadCam: Scalars["Boolean"];
  name: Scalars["String"];
  operationTemplateIds: Array<Scalars["Int"]>;
};

export type UpdateProcessTemplateInput = {
  name: Scalars["String"];
  order: Scalars["Int"];
  processSubCodeIds?: InputMaybe<Array<Scalars["Int"]>>;
};

export type UpdateRegularMaintenanceInput = {
  frequency: RegularMaintenanceFrequencyEnum;
  name: Scalars["String"];
  number: Scalars["String"];
};

export enum ProcessCodesFilterEnum {
  Code = "CODE",
}

export enum ProcessCodesSorterEnum {
  Code = "CODE",
}

export type AdminFormFragment = {
  __typename?: "Admin";
  username: string;
  firstname: string;
  surname: string;
  isSuperAdmin: boolean;
};

export type UpdateLoggedAdminMutationVariables = Exact<{
  input: UpdateLoggedAdminInput;
}>;

export type UpdateLoggedAdminMutation = {
  __typename?: "Mutation";
  updateLoggedAdmin: { __typename?: "Admin"; id: number };
};

export type UpdateLoggedAdminPasswordMutationVariables = Exact<{
  input: UpdateLoggedAdminPasswordInput;
}>;

export type UpdateLoggedAdminPasswordMutation = {
  __typename?: "Mutation";
  updateLoggedAdminPassword: boolean;
};

export type CreateAdminMutationVariables = Exact<{
  input: CreateAdminInput;
}>;

export type CreateAdminMutation = {
  __typename?: "Mutation";
  createAdmin: {
    __typename?: "Admin";
    id: number;
    username: string;
    firstname: string;
    surname: string;
    isSuperAdmin: boolean;
  };
};

export type UpdateAdminMutationVariables = Exact<{
  id: Scalars["Int"];
  input: UpdateAdminInput;
}>;

export type UpdateAdminMutation = {
  __typename?: "Mutation";
  updateAdmin: {
    __typename?: "Admin";
    id: number;
    username: string;
    firstname: string;
    surname: string;
    isSuperAdmin: boolean;
  };
};

export type DeleteAdminMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeleteAdminMutation = {
  __typename?: "Mutation";
  deleteAdmin: boolean;
};

export type AdminQueryVariables = Exact<{
  id: Scalars["Int"];
}>;

export type AdminQuery = {
  __typename?: "Query";
  admin: {
    __typename?: "Admin";
    id: number;
    username: string;
    firstname: string;
    surname: string;
    isSuperAdmin: boolean;
  };
};

export type AdminListQueryVariables = Exact<{
  input?: InputMaybe<AdminsInput>;
}>;

export type AdminListQuery = {
  __typename?: "Query";
  admins: {
    __typename?: "Admins";
    search: string;
    items: Array<{
      __typename?: "Admin";
      id: number;
      username: string;
      firstname: string;
      surname: string;
      isSuperAdmin: boolean;
    }>;
    filter: Array<{
      __typename?: "AdminsFilterParent";
      filter: Array<{
        __typename?: "AdminsFilter";
        column: AdminsFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "AdminsSorter";
      column: AdminsSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type DashboardStatusQueryVariables = Exact<{ [key: string]: never }>;

export type DashboardStatusQuery = {
  __typename?: "Query";
  dashboard: {
    __typename?: "AdminDashboard";
    tickets: number;
    pinnedTickets: number;
    urgentTickets: number;
    laborers: number;
    conflicts: number;
  };
};

export type DeviceQueryVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeviceQuery = {
  __typename?: "Query";
  device: {
    __typename?: "Device";
    id: number;
    number: string;
    name: string;
    note: string;
    laborerId: number;
    laborer: {
      __typename?: "Laborer";
      id: number;
      state: LaborerStateEnum;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
      ticketLimit: number;
      isCadCam: boolean;
    };
  };
};

export type CopyDeviceMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type CopyDeviceMutation = {
  __typename?: "Mutation";
  copyDevice: { __typename?: "Device"; name: string };
};

export type DeviceFormFragment = {
  __typename?: "Device";
  number: string;
  name: string;
  note: string;
  laborerId: number;
};

export type CreateDeviceMutationVariables = Exact<{
  input: DeviceInput;
}>;

export type CreateDeviceMutation = {
  __typename?: "Mutation";
  createDevice: {
    __typename?: "Device";
    id: number;
    laborerId: number;
    number: string;
    name: string;
    note: string;
    laborer: { __typename?: "Laborer"; firstname: string; surname: string };
  };
};

export type UpdateDeviceMutationVariables = Exact<{
  id: Scalars["Int"];
  input: DeviceInput;
}>;

export type UpdateDeviceMutation = {
  __typename?: "Mutation";
  updateDevice: {
    __typename?: "Device";
    id: number;
    laborerId: number;
    number: string;
    name: string;
    note: string;
    laborer: { __typename?: "Laborer"; firstname: string; surname: string };
  };
};

export type DeleteDeviceMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeleteDeviceMutation = {
  __typename?: "Mutation";
  deleteDevice: boolean;
};

export type DeviceListQueryVariables = Exact<{
  input?: InputMaybe<DevicesInput>;
}>;

export type DeviceListQuery = {
  __typename?: "Query";
  devices: {
    __typename?: "Devices";
    search: string;
    items: Array<{
      __typename?: "Device";
      id: number;
      laborerId: number;
      number: string;
      name: string;
      note: string;
      laborer: { __typename?: "Laborer"; firstname: string; surname: string };
    }>;
    filter: Array<{
      __typename?: "DevicesFilterParent";
      filter: Array<{
        __typename?: "DevicesFilter";
        column: DevicesFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "DevicesSorter";
      column: DevicesSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type CreateLaborerEventMutationVariables = Exact<{
  input: CreateLaborerEventInput;
}>;

export type CreateLaborerEventMutation = {
  __typename?: "Mutation";
  createLaborerEvent: Array<{ __typename?: "LaborerEvent"; id: number }>;
};

export type UpdateLaborerEventMutationVariables = Exact<{
  input: UpdateLaborerEventInput;
}>;

export type UpdateLaborerEventMutation = {
  __typename?: "Mutation";
  updateLaborerEvent: {
    __typename?: "LaborerEventsGroup";
    ids: Array<{ __typename?: "Id"; id: string }>;
  };
};

export type DeleteLaborerEventMutationVariables = Exact<{
  input: DeleteLaborerEventInput;
}>;

export type DeleteLaborerEventMutation = {
  __typename?: "Mutation";
  deleteLaborerEvent: boolean;
};

export type LaborerEventListQueryVariables = Exact<{
  input?: InputMaybe<LaborerEventsInput>;
}>;

export type LaborerEventListQuery = {
  __typename?: "Query";
  laborerEvents: {
    __typename?: "LaborerEvents";
    search: string;
    items: Array<{
      __typename?: "LaborerEventsCustom";
      laborerId: number;
      type: LaborerEventTypeEnum;
      fromDate: any;
      toDate: any;
      hours: number;
      isHalfDay: boolean;
      ids: Array<{ __typename?: "Id"; id: string }>;
    }>;
    filter: Array<{
      __typename?: "LaborerEventsFilterParent";
      filter: Array<{
        __typename?: "LaborerEventsFilter";
        column: LaborerEventsFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "LaborerEventsSorter";
      column: LaborerEventsSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type ExportLaborerEventsQueryVariables = Exact<{
  input: ExportLaborerEventsInput;
}>;

export type ExportLaborerEventsQuery = {
  __typename?: "Query";
  exportLaborerEvents: string;
};

export type LaborerQueryVariables = Exact<{
  id: Scalars["Int"];
}>;

export type LaborerQuery = {
  __typename?: "Query";
  laborer: {
    __typename?: "Laborer";
    id: number;
    state: LaborerStateEnum;
    status: LaborerStatusEnum;
    firstname: string;
    surname: string;
    username: string;
    ticketLimit: number;
    isCadCam: boolean;
  };
};

export type UpdateLaborerMutationVariables = Exact<{
  id: Scalars["Int"];
  input: UpdateLaborerInput;
}>;

export type UpdateLaborerMutation = {
  __typename?: "Mutation";
  updateLaborer: {
    __typename?: "Laborer";
    id: number;
    state: LaborerStateEnum;
    status: LaborerStatusEnum;
    firstname: string;
    surname: string;
    username: string;
    ticketLimit: number;
    isCadCam: boolean;
  };
};

export type InviteLaborerMutationVariables = Exact<{
  input: InviteLaborerInput;
  id: Scalars["Int"];
}>;

export type InviteLaborerMutation = {
  __typename?: "Mutation";
  inviteLaborer: boolean;
};

export type SyncLaborersMutationVariables = Exact<{ [key: string]: never }>;

export type SyncLaborersMutation = {
  __typename?: "Mutation";
  syncLaborers: boolean;
};

export type LaborerListQueryVariables = Exact<{
  input?: InputMaybe<LaborersInput>;
}>;

export type LaborerListQuery = {
  __typename?: "Query";
  laborers: {
    __typename?: "Laborers";
    search: string;
    items: Array<{
      __typename?: "Laborer";
      id: number;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
      username: string;
    }>;
    filter: Array<{
      __typename?: "LaborersFilterParent";
      filter: Array<{
        __typename?: "LaborersFilter";
        column: LaborersFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "LaborersSorter";
      column: LaborersSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type LaborersQueryVariables = Exact<{ [key: string]: never }>;

export type LaborersQuery = {
  __typename?: "Query";
  laborers: {
    __typename?: "Laborers";
    items: Array<{
      __typename?: "Laborer";
      id: number;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
      username: string;
    }>;
  };
};

export type MaintenanceFormFragment = {
  __typename?: "Maintenance";
  id: number;
  type: MaintenanceTypeEnum;
  state: MaintenanceStateEnum;
  plannedDate: any;
  description: string;
  deviceId: number;
  maintenanceDate?: any | null;
  report?: string | null;
  maintainedBy?: string | null;
  price?: number | null;
  responsiblePerson: {
    __typename?: "Admin";
    id: number;
    firstname: string;
    surname: string;
  };
  device: {
    __typename?: "Device";
    id: number;
    number: string;
    name: string;
    note: string;
    laborerId: number;
    laborer: {
      __typename?: "Laborer";
      id: number;
      state: LaborerStateEnum;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
      ticketLimit: number;
      isCadCam: boolean;
    };
  };
};

export type UpdateMaintenanceMutationVariables = Exact<{
  id: Scalars["Int"];
  input: UpdateMaintenanceInput;
}>;

export type UpdateMaintenanceMutation = {
  __typename?: "Mutation";
  updateMaintenance: {
    __typename?: "Maintenance";
    id: number;
    type: MaintenanceTypeEnum;
    state: MaintenanceStateEnum;
    plannedDate: any;
    description: string;
    deviceId: number;
    maintenanceDate?: any | null;
    report?: string | null;
    maintainedBy?: string | null;
    price?: number | null;
    responsiblePerson: {
      __typename?: "Admin";
      id: number;
      firstname: string;
      surname: string;
    };
    device: {
      __typename?: "Device";
      id: number;
      number: string;
      name: string;
      note: string;
      laborerId: number;
      laborer: {
        __typename?: "Laborer";
        id: number;
        state: LaborerStateEnum;
        status: LaborerStatusEnum;
        firstname: string;
        surname: string;
        ticketLimit: number;
        isCadCam: boolean;
      };
    };
  };
};

export type CreateMaintenanceMutationVariables = Exact<{
  input: CreateMaintenanceInput;
}>;

export type CreateMaintenanceMutation = {
  __typename?: "Mutation";
  createMaintenance: {
    __typename?: "Maintenance";
    id: number;
    type: MaintenanceTypeEnum;
    state: MaintenanceStateEnum;
    plannedDate: any;
    description: string;
    deviceId: number;
    maintenanceDate?: any | null;
    report?: string | null;
    maintainedBy?: string | null;
    price?: number | null;
    responsiblePerson: {
      __typename?: "Admin";
      id: number;
      firstname: string;
      surname: string;
    };
    device: {
      __typename?: "Device";
      id: number;
      number: string;
      name: string;
      note: string;
      laborerId: number;
      laborer: {
        __typename?: "Laborer";
        id: number;
        state: LaborerStateEnum;
        status: LaborerStatusEnum;
        firstname: string;
        surname: string;
        ticketLimit: number;
        isCadCam: boolean;
      };
    };
  };
};

export type DeleteMaintenanceMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeleteMaintenanceMutation = {
  __typename?: "Mutation";
  deleteMaintenance: boolean;
};

export type MaintenanceListQueryVariables = Exact<{
  input?: InputMaybe<MaintenancesInput>;
}>;

export type MaintenanceListQuery = {
  __typename?: "Query";
  maintenances: {
    __typename?: "Maintenances";
    search: string;
    items: Array<{
      __typename?: "Maintenance";
      id: number;
      type: MaintenanceTypeEnum;
      state: MaintenanceStateEnum;
      plannedDate: any;
      description: string;
      deviceId: number;
      maintenanceDate?: any | null;
      report?: string | null;
      maintainedBy?: string | null;
      price?: number | null;
      responsiblePerson: {
        __typename?: "Admin";
        id: number;
        firstname: string;
        surname: string;
      };
      device: {
        __typename?: "Device";
        id: number;
        number: string;
        name: string;
        note: string;
        laborerId: number;
        laborer: {
          __typename?: "Laborer";
          id: number;
          state: LaborerStateEnum;
          status: LaborerStatusEnum;
          firstname: string;
          surname: string;
          ticketLimit: number;
          isCadCam: boolean;
        };
      };
    }>;
    filter: Array<{
      __typename?: "MaintenancesFilterParent";
      filter: Array<{
        __typename?: "MaintenancesFilter";
        column: MaintenancesFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "MaintenancesSorter";
      column: MaintenancesSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type MaintenanceQueryVariables = Exact<{
  id: Scalars["Int"];
}>;

export type MaintenanceQuery = {
  __typename?: "Query";
  maintenance: {
    __typename?: "Maintenance";
    id: number;
    type: MaintenanceTypeEnum;
    state: MaintenanceStateEnum;
    plannedDate: any;
    description: string;
    responsiblePersonId: number;
    deviceId: number;
    maintenanceDate?: any | null;
    report?: string | null;
    maintainedBy?: string | null;
    price?: number | null;
    responsiblePerson: {
      __typename?: "Admin";
      id: number;
      firstname: string;
      surname: string;
      isSuperAdmin: boolean;
      username: string;
    };
    device: {
      __typename?: "Device";
      id: number;
      number: string;
      name: string;
      note: string;
      laborerId: number;
      laborer: {
        __typename?: "Laborer";
        id: number;
        state: LaborerStateEnum;
        status: LaborerStatusEnum;
        firstname: string;
        surname: string;
        username: string;
        ticketLimit: number;
        isCadCam: boolean;
      };
    };
  };
};

export type Unnamed_1_QueryVariables = Exact<{ [key: string]: never }>;

export type Unnamed_1_Query = {
  __typename?: "Query";
  devices: {
    __typename?: "Devices";
    items: Array<{ __typename?: "Device"; id: number; name: string }>;
  };
  laborers: {
    __typename?: "Laborers";
    items: Array<{
      __typename?: "Laborer";
      id: number;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
    }>;
  };
};

export type CompleteMaintenanceMutationVariables = Exact<{
  input: CompleteMaintenanceInput;
  id: Scalars["Int"];
}>;

export type CompleteMaintenanceMutation = {
  __typename?: "Mutation";
  completeMaintenance: { __typename?: "Maintenance"; id: number };
};

export type OperationTemplateFormFragment = {
  __typename?: "OperationTemplate";
  name: string;
  value: number;
  valueTwo: number;
  minimumTime: number;
  minimumTimeTwo: number;
  comfortTime: number;
  comfortTimeTwo: number;
};

export type CreateOperationTemplateMutationVariables = Exact<{
  input: CreateOperationTemplateInput;
}>;

export type CreateOperationTemplateMutation = {
  __typename?: "Mutation";
  createOperationTemplate: {
    __typename?: "OperationTemplate";
    id: number;
    name: string;
    value: number;
    valueTwo: number;
    minimumTime: number;
    minimumTimeTwo: number;
    comfortTime: number;
    comfortTimeTwo: number;
  };
};

export type UpdateOperationTemplateMutationVariables = Exact<{
  id: Scalars["Float"];
  input: UpdateOperationTemplateInput;
}>;

export type UpdateOperationTemplateMutation = {
  __typename?: "Mutation";
  updateOperationTemplate: {
    __typename?: "OperationTemplate";
    id: number;
    name: string;
    value: number;
    valueTwo: number;
    minimumTime: number;
    minimumTimeTwo: number;
    comfortTime: number;
    comfortTimeTwo: number;
  };
};

export type DeleteOperationTemplateMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeleteOperationTemplateMutation = {
  __typename?: "Mutation";
  deleteOperationTemplate: boolean;
};

export type OperationTemplateListQueryVariables = Exact<{
  input?: InputMaybe<OperationTemplatesInput>;
}>;

export type OperationTemplateListQuery = {
  __typename?: "Query";
  operationTemplates: {
    __typename?: "OperationTemplates";
    search: string;
    items: Array<{
      __typename?: "OperationTemplate";
      id: number;
      name: string;
      value: number;
      valueTwo: number;
      minimumTime: number;
      minimumTimeTwo: number;
      comfortTime: number;
      comfortTimeTwo: number;
    }>;
    filter: Array<{
      __typename?: "OperationTemplatesFilterParent";
      filter: Array<{
        __typename?: "OperationTemplatesFilter";
        column: OperationTemplatesFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "OperationTemplatesSorter";
      column: OperationTemplatesSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type ProcessCodeFormFragment = {
  __typename?: "ProcessCode";
  code: string;
  processTemplate?: { __typename?: "ProcessTemplate"; id: number } | null;
};

export type UpdateProcessCodeMutationVariables = Exact<{
  code: Scalars["String"];
  input: ProcessCodeInput;
}>;

export type UpdateProcessCodeMutation = {
  __typename?: "Mutation";
  updateProcessCode: {
    __typename?: "ProcessCode";
    code: string;
    processTemplate?: { __typename?: "ProcessTemplate"; id: number } | null;
  };
};

export type ProcessCodeListQueryVariables = Exact<{
  input?: InputMaybe<ProcessCodesInput>;
}>;

export type ProcessCodeListQuery = {
  __typename?: "Query";
  processCodes: {
    __typename?: "ProcessCodes";
    search: string;
    items: Array<{
      __typename?: "ProcessCode";
      code: string;
      processTemplate?: { __typename?: "ProcessTemplate"; id: number } | null;
    }>;
    filter: Array<{
      __typename?: "ProcessCodesFilterParent";
      filter: Array<{
        __typename?: "ProcessCodesFilter";
        column: ProcessCodesFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "ProcessCodesSorter";
      column: ProcessCodesSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type ProcessSubCodeFormFragment = {
  __typename?: "ProcessSubCode";
  name: string;
  code: string;
  operationTemplates: Array<{
    __typename?: "OperationTemplate";
    id: number;
    name: string;
  }>;
};

export type CreateProcessSubCodeMutationVariables = Exact<{
  input: CreateProcessSubCodeInput;
}>;

export type CreateProcessSubCodeMutation = {
  __typename?: "Mutation";
  createProcessSubCode: {
    __typename?: "ProcessSubCode";
    id: number;
    name: string;
    code: string;
    operationTemplates: Array<{
      __typename?: "OperationTemplate";
      id: number;
      name: string;
    }>;
  };
};

export type UpdateProcessSubCodeMutationVariables = Exact<{
  id: Scalars["Int"];
  input: UpdateProcessSubCodeInput;
}>;

export type UpdateProcessSubCodeMutation = {
  __typename?: "Mutation";
  updateProcessSubCode: {
    __typename?: "ProcessSubCode";
    id: number;
    name: string;
    code: string;
    operationTemplates: Array<{
      __typename?: "OperationTemplate";
      id: number;
      name: string;
    }>;
  };
};

export type DeleteProcessSubCodeMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeleteProcessSubCodeMutation = {
  __typename?: "Mutation";
  deleteProcessSubCode: boolean;
};

export type ProcessSubCodeListQueryVariables = Exact<{
  input?: InputMaybe<ProcessSubCodesInput>;
}>;

export type ProcessSubCodeListQuery = {
  __typename?: "Query";
  processSubCodes: {
    __typename?: "ProcessSubCodes";
    search: string;
    items: Array<{
      __typename?: "ProcessSubCode";
      id: number;
      name: string;
      code: string;
      operationTemplates: Array<{
        __typename?: "OperationTemplate";
        id: number;
        name: string;
      }>;
    }>;
    filter: Array<{
      __typename?: "ProcessSubCodesFilterParent";
      filter: Array<{
        __typename?: "ProcessSubCodesFilter";
        column: ProcessSubCodesFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "ProcessSubCodesSorter";
      column: ProcessSubCodesSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type CreateProcessTemplateMutationVariables = Exact<{
  input: CreateProcessTemplateInput;
}>;

export type CreateProcessTemplateMutation = {
  __typename?: "Mutation";
  createProcessTemplate: {
    __typename?: "ProcessTemplate";
    id: number;
    name: string;
    order: number;
    processSubCodeIds: Array<number>;
    processSubCodes: Array<{
      __typename?: "ProcessSubCode";
      id: number;
      name: string;
      code: string;
    }>;
  };
};

export type UpdateProcessTemplateMutationVariables = Exact<{
  id: Scalars["Int"];
  input: UpdateProcessTemplateInput;
}>;

export type UpdateProcessTemplateMutation = {
  __typename?: "Mutation";
  updateProcessTemplate: {
    __typename?: "ProcessTemplate";
    id: number;
    name: string;
    order: number;
    processSubCodeIds: Array<number>;
    processSubCodes: Array<{
      __typename?: "ProcessSubCode";
      id: number;
      name: string;
      code: string;
    }>;
  };
};

export type DeleteProcessTemplateMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeleteProcessTemplateMutation = {
  __typename?: "Mutation";
  deleteProcessTemplate: boolean;
};

export type ProcessTemplateListQueryVariables = Exact<{
  input?: InputMaybe<ProcessTemplatesInput>;
}>;

export type ProcessTemplateListQuery = {
  __typename?: "Query";
  processTemplates: {
    __typename?: "ProcessTemplates";
    search: string;
    items: Array<{
      __typename?: "ProcessTemplate";
      id: number;
      name: string;
      order: number;
      processSubCodeIds: Array<number>;
      processSubCodes: Array<{
        __typename?: "ProcessSubCode";
        id: number;
        name: string;
        code: string;
      }>;
    }>;
    filter: Array<{
      __typename?: "ProcessTemplatesFilterParent";
      filter: Array<{
        __typename?: "ProcessTemplatesFilter";
        column: ProcessTemplatesFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "ProcessTemplatesSorter";
      column: ProcessTemplatesSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type RegularMaintenanceFormFragment = {
  __typename?: "RegularMaintenance";
  id: number;
  name: string;
  number: string;
  frequency: RegularMaintenanceFrequencyEnum;
  deviceId: number;
  device: {
    __typename?: "Device";
    id: number;
    number: string;
    name: string;
    note: string;
    laborerId: number;
    laborer: {
      __typename?: "Laborer";
      id: number;
      state: LaborerStateEnum;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
      ticketLimit: number;
      isCadCam: boolean;
    };
  };
};

export type CreateRegularMaintenanceMutationVariables = Exact<{
  input: CreateRegularMaintenanceInput;
}>;

export type CreateRegularMaintenanceMutation = {
  __typename?: "Mutation";
  createRegularMaintenance: {
    __typename?: "RegularMaintenance";
    id: number;
    name: string;
    number: string;
    frequency: RegularMaintenanceFrequencyEnum;
    deviceId: number;
    device: {
      __typename?: "Device";
      id: number;
      number: string;
      name: string;
      note: string;
      laborerId: number;
      laborer: {
        __typename?: "Laborer";
        id: number;
        state: LaborerStateEnum;
        status: LaborerStatusEnum;
        firstname: string;
        surname: string;
        ticketLimit: number;
        isCadCam: boolean;
      };
    };
  };
};

export type UpdateRegularMaintenanceMutationVariables = Exact<{
  id: Scalars["Int"];
  input: UpdateRegularMaintenanceInput;
}>;

export type UpdateRegularMaintenanceMutation = {
  __typename?: "Mutation";
  updateRegularMaintenance: {
    __typename?: "RegularMaintenance";
    id: number;
    name: string;
    number: string;
    frequency: RegularMaintenanceFrequencyEnum;
    deviceId: number;
    device: {
      __typename?: "Device";
      id: number;
      number: string;
      name: string;
      note: string;
      laborerId: number;
      laborer: {
        __typename?: "Laborer";
        id: number;
        state: LaborerStateEnum;
        status: LaborerStatusEnum;
        firstname: string;
        surname: string;
        ticketLimit: number;
        isCadCam: boolean;
      };
    };
  };
};

export type DeleteRegularMaintenanceMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type DeleteRegularMaintenanceMutation = {
  __typename?: "Mutation";
  deleteRegularMaintenance: boolean;
};

export type RegularMaintenanceListQueryVariables = Exact<{
  input?: InputMaybe<RegularMaintenancesInput>;
}>;

export type RegularMaintenanceListQuery = {
  __typename?: "Query";
  regularMaintenances: {
    __typename?: "RegularMaintenances";
    search: string;
    items: Array<{
      __typename?: "RegularMaintenance";
      id: number;
      name: string;
      number: string;
      frequency: RegularMaintenanceFrequencyEnum;
      deviceId: number;
      device: {
        __typename?: "Device";
        id: number;
        number: string;
        name: string;
        note: string;
        laborerId: number;
        laborer: {
          __typename?: "Laborer";
          id: number;
          state: LaborerStateEnum;
          status: LaborerStatusEnum;
          firstname: string;
          surname: string;
          ticketLimit: number;
          isCadCam: boolean;
        };
      };
    }>;
    filter: Array<{
      __typename?: "RegularMaintenancesFilterParent";
      filter: Array<{
        __typename?: "RegularMaintenancesFilter";
        column: RegularMaintenancesFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "RegularMaintenancesSorter";
      column: RegularMaintenancesSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type CalendarQueryVariables = Exact<{
  input: CalendarInput;
}>;

export type CalendarQuery = {
  __typename?: "Query";
  calendar: Array<{
    __typename?: "Calendar";
    date: any;
    conflicts: number;
    laborerIds: Array<number>;
    laborers: Array<{
      __typename?: "Laborer";
      id: number;
      state: LaborerStateEnum;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
    }>;
  }>;
};

export type TicketQueryVariables = Exact<{
  id: Scalars["Int"];
}>;

export type TicketQuery = {
  __typename?: "Query";
  ticket: {
    __typename?: "Ticket";
    isPinned: boolean;
    important: boolean;
    created: any;
    problem: boolean;
    id: number;
    number: string;
    doctorNote: string;
    laboratoryNote?: string | null;
    patientId: number;
    processId: number;
    doctorId: number;
    externalId: string;
    price?: number | null;
    laborerId: number;
    externalChat: Array<{
      __typename?: "Chat";
      message: string;
      sent: any;
      isRead: boolean;
      sender?: {
        __typename?: "Sender";
        firstname: string;
        surname: string;
      } | null;
      recipient?: {
        __typename?: "Recipient";
        firstname: string;
        surname: string;
      } | null;
      thumbnail?: { __typename?: "Thumbnail"; id: string } | null;
    }>;
    internalChat: Array<{
      __typename?: "Chat";
      message: string;
      sent: any;
      isRead: boolean;
      sender?: {
        __typename?: "Sender";
        firstname: string;
        surname: string;
      } | null;
      recipient?: {
        __typename?: "Recipient";
        firstname: string;
        surname: string;
      } | null;
      thumbnail?: { __typename?: "Thumbnail"; id: string } | null;
    }>;
    macros: Array<{
      __typename?: "Macro";
      subCode: string;
      items: number;
      price: number;
      name: string;
    }>;
    laborer: {
      __typename?: "Laborer";
      id: number;
      state: LaborerStateEnum;
      status: LaborerStatusEnum;
      firstname: string;
      surname: string;
      ticketLimit: number;
      isCadCam: boolean;
    };
    process: {
      __typename?: "Process";
      id: number;
      deadline: any;
      operationsIds: Array<number>;
      operations: Array<{
        __typename?: "Operation";
        id: number;
        templateId: number;
        laborerId: number;
        conflict: boolean;
        status: OperationStatusEnum;
        skipped: boolean;
        template: {
          __typename?: "OperationTemplate";
          id: number;
          name: string;
          value: number;
          valueTwo: number;
          minimumTime: number;
          minimumTimeTwo: number;
          comfortTime: number;
          comfortTimeTwo: number;
          processSubCodeIds: Array<number>;
          processSubCodes: Array<{
            __typename?: "ProcessSubCode";
            id: number;
            name: string;
            code: string;
            isCadCam: boolean;
          }>;
        };
        laborer: {
          __typename?: "Laborer";
          id: number;
          state: LaborerStateEnum;
          status: LaborerStatusEnum;
          firstname: string;
          surname: string;
          ticketLimit: number;
          isCadCam: boolean;
        };
      }>;
    };
    doctor: {
      __typename?: "Doctor";
      id: number;
      fullName: string;
      email: string;
      phone: string;
      companyName: string;
      nameAddress?: string | null;
      street: string;
      city: string;
      postCode: string;
    };
    patient: {
      __typename?: "Patient";
      id: number;
      firstname: string;
      surname: string;
      email?: string | null;
      phone?: string | null;
    };
    operations: Array<{
      __typename?: "Operation";
      id: number;
      templateId: number;
      laborerId: number;
      conflict: boolean;
      status: OperationStatusEnum;
      estimateFrom: any;
      estimateTo: any;
      realFrom?: any | null;
      realTo?: any | null;
      skipped: boolean;
      template: {
        __typename?: "OperationTemplate";
        id: number;
        name: string;
        value: number;
        valueTwo: number;
        minimumTime: number;
        minimumTimeTwo: number;
        comfortTime: number;
        comfortTimeTwo: number;
        processSubCodeIds: Array<number>;
        processSubCodes: Array<{
          __typename?: "ProcessSubCode";
          id: number;
          name: string;
          code: string;
          isCadCam: boolean;
        }>;
      };
      laborer: {
        __typename?: "Laborer";
        id: number;
        state: LaborerStateEnum;
        status: LaborerStatusEnum;
        firstname: string;
        surname: string;
        ticketLimit: number;
        isCadCam: boolean;
      };
    }>;
  };
};

export type ChangeDeadlineMutationVariables = Exact<{
  input: ChangeDeadlineInput;
}>;

export type ChangeDeadlineMutation = {
  __typename?: "Mutation";
  changeDeadline: boolean;
};

export type SolveTicketProblemMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type SolveTicketProblemMutation = {
  __typename?: "Mutation";
  solveTicketProblem: boolean;
};

export type MakeTicketProblemMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type MakeTicketProblemMutation = {
  __typename?: "Mutation";
  makeTicketProblem: boolean;
};

export type PinTicketMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type PinTicketMutation = { __typename?: "Mutation"; pinTicket: boolean };

export type UnpinTicketMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type UnpinTicketMutation = {
  __typename?: "Mutation";
  unpinTicket: boolean;
};

export type MakeTicketImportantMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type MakeTicketImportantMutation = {
  __typename?: "Mutation";
  makeTicketImportant: boolean;
};

export type MakeTicketNotImportantMutationVariables = Exact<{
  id: Scalars["Int"];
}>;

export type MakeTicketNotImportantMutation = {
  __typename?: "Mutation";
  makeTicketNotImportant: boolean;
};

export type PinnedTicketListQueryVariables = Exact<{
  input?: InputMaybe<TicketsInput>;
}>;

export type PinnedTicketListQuery = {
  __typename?: "Query";
  pinnedTickets: {
    __typename?: "Tickets";
    search: string;
    items: Array<{
      __typename?: "Ticket";
      created: any;
      id: number;
      important: boolean;
      number: string;
      price?: number | null;
      problem: boolean;
      urgent: boolean;
      laborer: { __typename?: "Laborer"; firstname: string; surname: string };
      process: { __typename?: "Process"; deadline: any };
      doctor: { __typename?: "Doctor"; companyName: string };
      patient: { __typename?: "Patient"; firstname: string; surname: string };
      operations: Array<{
        __typename?: "Operation";
        status: OperationStatusEnum;
        laborer: { __typename?: "Laborer"; firstname: string; surname: string };
      }>;
    }>;
    filter: Array<{
      __typename?: "TicketsFilterParent";
      filter: Array<{
        __typename?: "TicketsFilter";
        column: TicketFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "TicketsSorter";
      column: TicketSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};

export type TicketListQueryVariables = Exact<{
  input?: InputMaybe<TicketsInput>;
}>;

export type TicketListQuery = {
  __typename?: "Query";
  tickets: {
    __typename?: "Tickets";
    search: string;
    items: Array<{
      __typename?: "Ticket";
      created: any;
      id: number;
      important: boolean;
      isPinned: boolean;
      number: string;
      price?: number | null;
      problem: boolean;
      urgent: boolean;
      laborer: { __typename?: "Laborer"; firstname: string; surname: string };
      process: { __typename?: "Process"; deadline: any };
      doctor: { __typename?: "Doctor"; companyName: string };
      patient: { __typename?: "Patient"; firstname: string; surname: string };
      operations: Array<{
        __typename?: "Operation";
        status: OperationStatusEnum;
        laborer: { __typename?: "Laborer"; firstname: string; surname: string };
      }>;
    }>;
    filter: Array<{
      __typename?: "TicketsFilterParent";
      filter: Array<{
        __typename?: "TicketsFilter";
        column: TicketFilterEnum;
        operator: FilterOperatorEnum;
        values: Array<string>;
      }>;
    }>;
    sorter: Array<{
      __typename?: "TicketsSorter";
      column: TicketSorterEnum;
      direction: SorterDirectionEnum;
    }>;
    pager: {
      __typename?: "Pager";
      page: number;
      size: number;
      prev: number;
      next: number;
      last: number;
      total: number;
    };
  };
};
