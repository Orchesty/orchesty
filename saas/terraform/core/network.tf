resource "google_compute_network" "default" {
  name                            = "default"
  auto_create_subnetworks         = false
  delete_default_routes_on_create = true
}

// all subnets fit in 10.128.0.0/17
// 10.128.0.0/16 is reserved for this region
locals {
  default_regional_subnet = "10.128.0.0/16"
}

resource "google_compute_subnetwork" "public" {
  name          = "public"
  ip_cidr_range = "10.128.81.0/24"
  region        = var.region
  network       = google_compute_network.default.id
}

// block: 10.128.83.0/24 is reserved for GKE master ranges
// block: 10.128.83.0/28 is used for gke1

resource "google_compute_subnetwork" "data" {
  name          = "data"
  ip_cidr_range = "10.128.84.0/22"
  region        = var.region
  network       = google_compute_network.default.id
}

// assuming 32 pods per tenant
resource "google_compute_subnetwork" "gke1" {
  name = "gke1"

  // ~64 [nodes]
  ip_cidr_range = "10.128.80.0/26"

  region  = var.region
  network = google_compute_network.default.id

  // 512 [per-node pod ranges, ~256 pods per node] * 16 (nodes) * 2 (half-density scenario) = 16384 (ips)
  secondary_ip_range {
    range_name    = "pods-range"
    ip_cidr_range = "10.128.0.0/18"
  }

  // 128 [tenants] * 32 [services] == 4096 [ips]
  secondary_ip_range {
    range_name    = "services-range"
    ip_cidr_range = "10.128.64.0/20"
  }
}

resource "google_compute_route" "default" {
  description      = "Default route to internet"
  name             = "default"
  dest_range       = "0.0.0.0/0"
  network          = google_compute_network.default.self_link
  next_hop_gateway = "default-internet-gateway"
}

# reference handmade static IP to be sure TF will not touch it
# nicetohave: create by TF, prevent deletion by IaM
data "google_compute_address" "nat_addr1" {
  name   = "nat-addr1"
  region = var.region
  
}

resource "google_compute_router" "default" {
  name    = "default"
  network = google_compute_network.default.self_link
}

resource "google_compute_router_nat" "default" {
  name   = "default"
  router = google_compute_router.default.name

  nat_ip_allocate_option = "MANUAL_ONLY"
  nat_ips                = [data.google_compute_address.nat_addr1.self_link]

  source_subnetwork_ip_ranges_to_nat = "ALL_SUBNETWORKS_ALL_IP_RANGES"
  log_config {
    enable = false
    filter = "ALL"
  }
}
