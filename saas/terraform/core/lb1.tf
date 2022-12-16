## managed certificates

locals {
  cert_resources = {
    #"a" = google_compute_managed_ssl_certificate.lb1_a
    #"b" = google_compute_managed_ssl_certificate.lb1_b
  }

  # only first zone is active outside prod
  lb1_active_zones = terraform.workspace == "prod" ? var.zones : [var.zones[0]]
}

#resource "google_compute_managed_ssl_certificate" "lb1_a" {
#  name = "lb1-a"
#
#  managed {
#    domains = var.lb1_domains.a
#  }
#
#  depends_on = [
#    google_dns_managed_zone.public,
#  ]
#}

# resource "google_compute_managed_ssl_certificate" "lb1_b" {
#   name = "lb1-b"

#   managed {
#     domains = var.lb1_domains.b
#   }

#   depends_on = [
#     google_dns_managed_zone.public,
#   ]
# }

# reference handmade static IP to be sure TF will not touch it
# nicetohave: create by TF, prevent deletion by IaM
data "google_compute_global_address" "lb1" {
  name   = "lb1-addr"
}

data "google_compute_ssl_certificate" "lb1_imported_a" {
  name = "lb1-handmade-le003"
}

data "google_compute_ssl_certificate" "lb1_imported_b" {
  name = "lb1-handmade-le-1"
}

## main https forwarding rule and stuff

resource "google_compute_global_forwarding_rule" "lb1" {
  name       = "lb1"
  target     = google_compute_target_https_proxy.lb1.id
  port_range = "443"
  ip_address = data.google_compute_global_address.lb1.address
}

resource "google_compute_target_https_proxy" "lb1" {
  name             = "lb1"
  url_map          = google_compute_url_map.lb1.id
  ssl_certificates = [
    data.google_compute_ssl_certificate.lb1_imported_a.name,
    #data.google_compute_ssl_certificate.lb1_imported_b.name,
  ] #[for s in var.lb1_active_domains : local.cert_resources[s].id]
}

resource "google_compute_health_check" "default_tcp" {
  name               = "default-tcp-check"
  check_interval_sec = 10
  timeout_sec        = 5
  tcp_health_check {
    port_specification = "USE_SERVING_PORT"
  }
}
