resource "google_dns_managed_zone" "cloud" {
  name     = "cloud"
  dns_name = "${var.dns_zone_cloud}."
  dnssec_config {
    state = "on"
  }
}


resource "google_dns_record_set" "applinth_api" {
  name = "applinth-api.${var.dns_zone_cloud}."
  type = "A"
  ttl  = 300

  managed_zone = google_dns_managed_zone.cloud.name

  rrdatas = [google_compute_global_forwarding_rule.lb1.ip_address]
}

resource "google_dns_record_set" "applinth_mgmt_api" {
  name = "applinth-mgmt-api.${var.dns_zone_cloud}."
  type = "A"
  ttl  = 300

  managed_zone = google_dns_managed_zone.cloud.name

  rrdatas = [google_compute_global_forwarding_rule.lb1.ip_address]
}

resource "google_dns_record_set" "console" {
  name = "console.${var.dns_zone_cloud}."
  type = "A"
  ttl  = 300

  managed_zone = google_dns_managed_zone.cloud.name

  rrdatas = [google_compute_global_forwarding_rule.lb1.ip_address]
}

resource "google_dns_record_set" "instance_proxy_eu1" {
  name = "*.eu1.${var.dns_zone_cloud}."
  type = "A"
  ttl  = 300

  managed_zone = google_dns_managed_zone.cloud.name

  rrdatas = [google_compute_global_forwarding_rule.lb1.ip_address]
}

resource "google_dns_record_set" "tenant_proxy_eu1" {
  name = "*.tenant-eu1.${var.dns_zone_cloud}."
  type = "A"
  ttl  = 300

  managed_zone = google_dns_managed_zone.cloud.name

  rrdatas = [google_compute_global_forwarding_rule.lb1.ip_address]
}

resource "google_dns_record_set" "usccp" {
  name = "usccp.${var.dns_zone_cloud}."
  type = "A"
  ttl  = 300

  managed_zone = google_dns_managed_zone.cloud.name

  rrdatas = [google_compute_global_forwarding_rule.lb1.ip_address]
}
