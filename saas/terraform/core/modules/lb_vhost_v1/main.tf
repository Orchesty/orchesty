data "google_compute_network_endpoint_group" "neg" {
  for_each = toset(var.params.zones)
  name     = var.name
  zone     = "${var.params.region}-${each.value}"
}

resource "google_compute_backend_service" "backend" {
  name        = var.name
  port_name   = "http"
  protocol    = "HTTP"
  timeout_sec = var.timeout

  health_checks = [var.params.default_healthcheck_id]

  dynamic "backend" {
    # do not attach the backends to a NEG when initializing the workspace (or when vhost is disabled), GKE NEGs do not exist yet
    for_each = toset(!var.enabled ||  var.params.bootstrap ? [] : var.params.zones)
    content {
      group                 = data.google_compute_network_endpoint_group.neg[backend.value].id
      balancing_mode        = "RATE"
      max_rate_per_endpoint = 999
      max_utilization       = 0
    }
  }
}
