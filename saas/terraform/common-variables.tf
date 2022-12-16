variable "bootstrap" {
  description = "Set to 'true' once initialising a new environment"

  # Effects: (please keep up to date):
  # lb1_vhosts.tf: NEGs not attached

  type    = bool
  default = false
}

variable "project_id" {}

variable "region" {}

variable "zones" {
  type = list(any)
}

variable "dns_zone_cloud" {
  description = "DNS hostname for all Orchesty Cloud endpoints"
}
