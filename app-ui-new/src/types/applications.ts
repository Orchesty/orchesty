// Application types based on OpenAPI schema

export type ApplicationType = 'cron' | 'webhook';
export type AuthorizationType = 'basic' | 'oauth' | 'oauth2';
export type ApplicationStatus = 'available' | 'installed' | 'authorized' | 'activated';
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
  tab?: string; // Display name (publicName) e.g., "Authorization"
  formKey?: string; // API key for grouping e.g., "authorization_form"
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
  authorized?: boolean;
  applicationSettings: ApplicationSetting[];
  webhookSettings?: WebhookSetting[];
  worker?: string; // Custom field for grouping by worker
  logo?: string; // Base64 encoded SVG from API
  info?: string; // Markdown documentation from the worker SDK
}

export interface ApplicationWithStatus extends Application {
  status: ApplicationStatus;
  authorized?: boolean;
  logo?: string; // Base64 encoded SVG from API
}

export interface ApplicationQueryParams {
  status?: ApplicationStatus | 'all-installed';
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

// API response structure for application detail/install
export interface ApplicationSettingFieldApi {
  type: ApplicationSettingType;
  key: string;
  value: string | boolean | null;
  label: string;
  description: string;
  required: boolean;
  readOnly: boolean;
  disabled: boolean;
  choices: Array<Record<string, string>> | string[];
}

export interface ApplicationFormGroupApi {
  key: string;
  publicName: string;
  description: string;
  readOnly: boolean;
  fields: ApplicationSettingFieldApi[];
}

export interface ApplicationInstallApiResponse {
  name: string;
  authorization_type: AuthorizationType;
  application_type: ApplicationType;
  key: string;
  description: string;
  info: string;
  logo: string;
  isInstallable: boolean;
  applicationSettings?: Record<string, ApplicationFormGroupApi> | null;
  host: string;
  syncMethods?: string[];
  authorized?: boolean;
  enabled?: boolean;
}
