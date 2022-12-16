resource "google_compute_firewall" "default-ingress-allow-intra" {
  description   = "Allow internal ingress communication"
  name          = "default-ingress-allow-intra"
  direction     = "INGRESS"
  network       = google_compute_network.default.self_link
  source_ranges = [local.default_regional_subnet]
  allow {
    protocol = "all"
  }
}

resource "google_compute_firewall" "default-ingress-allow-ssh-from-list" {
  description = "Allow SSH connections from listed IPs"
  name        = "default-ingress-allow-ssh-from-list"
  direction   = "INGRESS"
  network     = google_compute_network.default.self_link
  target_tags = ["ssh"]

  # SSH allow list
  source_ranges = [
    "188.122.212.69", // HANABOSO office
    "89.248.0.0/16",  // husak.j home
  ]

  allow {
    protocol = "tcp"
    ports    = ["22"]
  }
}

resource "google_compute_firewall" "default-ingress-allow-iap" {
  description = "Allow ingress from Google IAP (web SSH)"
  name        = "default-ingress-allow-iap"
  direction   = "INGRESS"
  network     = google_compute_network.default.self_link

  # Well-known range (https://cloud.google.com/iap/docs/using-tcp-forwarding)
  source_ranges = ["35.235.240.0/20"]
  allow {
    protocol = "tcp"
    ports    = [22]
  }
}

resource "google_compute_firewall" "default-ingress-allow-lb" {
  description = "Allow ingress from loadbalancers and health checks"
  name        = "default-ingress-allow-lb"
  direction   = "INGRESS"
  network     = google_compute_network.default.self_link

  # Well-known range (https://cloud.google.com/load-balancing/docs/health-checks#fw-rule)
  source_ranges = [
    "35.191.0.0/16",
    "130.211.0.0/22",
  ]
  allow {
    protocol = "tcp"
  }
}
