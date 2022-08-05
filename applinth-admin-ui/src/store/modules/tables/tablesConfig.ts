import { api } from "../../../api";
import { ApiConfig } from "../../../types";
import { TablesNamespaces } from "./types";

interface Config {
  apiConfig: ApiConfig;
  reduceData: (data: any) => any;
}
type TablesConfig = { [index in TablesNamespaces]: Config };

export const tablesConfig: TablesConfig = {
  adminsTable: {
    apiConfig: api.admins.adminList,
    reduceData: (data) => data.admins,
  },
  devicesTable: {
    apiConfig: api.devices.deviceList,
    reduceData: (data) => data.devices,
  },
  laborersTable: {
    apiConfig: api.laborers.laborerList,
    reduceData: (data) => data.laborers,
  },
  laborerEventsTable: {
    apiConfig: api.laborerEvents.laborerEventList,
    reduceData: (data) => data.laborerEvents,
  },
  laborerTicketsTable: {
    apiConfig: api.tickets.ticketList,
    reduceData: (data) => data.tickets,
  },
  maintenancesTable: {
    apiConfig: api.maintenances.maintenanceList,
    reduceData: (data) => data.maintenances,
  },
  pinnedTicketsTable: {
    apiConfig: api.tickets.pinnedTicketList,
    reduceData: (data) => data.pinnedTickets,
  },
  processSubCodesTable: {
    apiConfig: api.processSubCodes.processSubCodeList,
    reduceData: (data) => data.processSubCodes,
  },
  processCodesTable: {
    apiConfig: api.processCodes.processCodeList,
    reduceData: (data) => data.processCodes,
  },
  processTemplatesTable: {
    apiConfig: api.processTemplates.processTemplateList,
    reduceData: (data) => data.processTemplates,
  },
  operationTemplatesTable: {
    apiConfig: api.operationTemplates.operationTemplateList,
    reduceData: (data) => data.operationTemplates,
  },
  regularMaintenancesTable: {
    apiConfig: api.regularMaintenances.regularMaintenanceList,
    reduceData: (data) => data.regularMaintenances,
  },
  ticketsTable: {
    apiConfig: api.tickets.ticketList,
    reduceData: (data) => data.tickets,
  },
};
