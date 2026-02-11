// Application types based on OpenAPI schema

export type ApplicationType = 'cron' | 'webhook';
export type AuthorizationType = 'basic' | 'oauth' | 'oauth2';
export type ApplicationStatus = 'uninstalled' | 'unauthorized' | 'authorized';
export type ApplicationSettingType = 'text' | 'number' | 'url' | 'password' | 'selectbox' | 'checkbox';

export interface Application {
  key: string;
  name: string;
  description: string;
  application_type: ApplicationType;
  authorization_type: AuthorizationType;
  worker?: string; // Custom field for grouping by worker
}

export interface ApplicationSetting {
  key: string;
  type: ApplicationSettingType;
  label: string;
  value?: string;
  description?: string;
  required?: boolean;
  readOnly?: boolean;
  disabled?: boolean;
  choices?: string[]; // For selectbox type
  tab?: string; // Custom field for organizing into tabs
}

export interface WebhookSetting {
  name: string;
  topology: string;
  default: boolean;
  enabled: boolean;
}

export interface ApplicationInstall {
  key: string;
  name: string;
  description: string;
  application_type: ApplicationType;
  authorization_type: AuthorizationType;
  authorized: boolean;
  applicationSettings: ApplicationSetting[];
  webhookSettings?: WebhookSetting[];
  worker?: string; // Custom field for grouping by worker
}

export interface ApplicationWithStatus extends Application {
  status: ApplicationStatus;
  authorized?: boolean;
}

export interface ApplicationQueryParams {
  status?: ApplicationStatus;
  worker?: string;
  search?: string;
}

export interface WorkerGroup {
  name: string;
  applications: ApplicationWithStatus[];
}

export interface ApplicationInstallResponse {
  id: string;
  key: string;
  user: string;
  authorized: boolean;
  settings: Record<string, unknown>;
  nonEncryptedSettings: Record<string, unknown>;
  created: string;
  updated: string;
  expires: string | null;
}

