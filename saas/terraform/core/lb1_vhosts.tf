locals {
  # "global" params for vhosts
  vhost_params = {
    region    = var.region,
    zones     = local.lb1_active_zones,
    bootstrap = var.bootstrap,

    default_healthcheck_id = google_compute_health_check.default_tcp.id
  }
}

module "applinth_api" {
  source  = "./modules/lb_vhost_v1"
  name    = "applinth-api"
  enabled = true
  params  = local.vhost_params
}

module "console" {
  source  = "./modules/lb_vhost_v1"
  name    = "console-applinth-ui"
  enabled = true
  params  = local.vhost_params
}

module "instance_proxy_eu1" {
  source  = "./modules/lb_vhost_v1"
  name    = "instance-proxy-eu1"
  enabled = true
  timeout = 30 // actually, only single instance (iqnb4x3vhd) needs this for now, think about it!
  params  = local.vhost_params
}

module "tenant_proxy_eu1" {
  source  = "./modules/lb_vhost_v1"
  name    = "tenant-proxy-eu1"
  enabled = true
  params  = local.vhost_params
}

module "usccp" {
  source  = "./modules/lb_vhost_v1"
  name    = "usccp"
  enabled = true
  params  = local.vhost_params
}


resource "google_compute_url_map" "lb1" {
  name = "lb1"

  default_url_redirect {
    host_redirect  = var.dns_zone_cloud
    https_redirect = true
    strip_query    = true
  }


  # applinth-api (broken in prod)

  host_rule {
    hosts        = ["applinth-api.${var.dns_zone_cloud}"]
    path_matcher = "applinth-api"
  }

  path_matcher {
    name            = "applinth-api"
    default_service = module.applinth_api.backend_service_id
  }


  # applinth-mgmt-api

  host_rule {
    hosts        = ["applinth-mgmt-api.${var.dns_zone_cloud}"]
    path_matcher = "applinth-api"
  }


  # console

  host_rule {
    hosts        = ["console.${var.dns_zone_cloud}"]
    path_matcher = "console"
  }

  path_matcher {
    name            = "console"
    default_service = module.console.backend_service_id
  }


  # instance-proxy-eu1

  host_rule {
    hosts        = ["*.eu1.${var.dns_zone_cloud}"]
    path_matcher = "instance-proxy-eu1"
  }

  path_matcher {
    name            = "instance-proxy-eu1"
    default_service = module.instance_proxy_eu1.backend_service_id
  }


  # tenant-proxy-eu1 (legacy)

  host_rule {
    hosts        = ["*.tenant-eu1.${var.dns_zone_cloud}"]
    path_matcher = "instance-proxy-eu1"
  }
  
  # USCCP

  host_rule {
    hosts        = ["usccp.${var.dns_zone_cloud}"]
    path_matcher = "usccp"
  }

  path_matcher {
    name            = "usccp"
    default_service = module.usccp.backend_service_id
  }
}
