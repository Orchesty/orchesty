locals {
  cluster_ha = terraform.workspace == "prod"
  cluster_location = local.cluster_ha ? var.region : "${var.region}-${var.zones[0]}"
}

data "google_compute_network" "default" {
  name = "default"
}

data "google_compute_subnetwork" "gke" {
  name   = "gke1"
  region = var.region
}


resource "google_service_account" "gke1_nodes" {
  account_id   = "gke1-nodes"
  display_name = "gke1 nodes service account"
}

resource "google_project_iam_member" "gke1_role" {
  for_each = toset([
    "roles/logging.logWriter",
    "roles/monitoring.metricWriter",

    # security: these should be moved to ESP-backed services' SAs
    "roles/servicemanagement.serviceController",
    "roles/cloudtrace.agent",
  ])

  project = var.project_id
  role    = each.value
  member = "serviceAccount:${google_service_account.gke1_nodes.email}"
}

resource "google_container_cluster" "gke1" {
  name     = "gke1"
  location = local.cluster_location

  release_channel {
      channel = "STABLE"
  }

  min_master_version = "1.22.10-gke.600"

  network    = data.google_compute_network.default.id
  subnetwork = data.google_compute_subnetwork.gke.id

  networking_mode = "VPC_NATIVE"

  ip_allocation_policy {
    cluster_secondary_range_name  = "services-range"
    services_secondary_range_name = "pods-range"
  }

  private_cluster_config {
    enable_private_nodes = true
    enable_private_endpoint = true
    
    # look for "GKE master ranges" in core/network.tf
    master_ipv4_cidr_block = "10.128.83.0/28"
  }

  master_authorized_networks_config {
    cidr_blocks {
      cidr_block = "10.128.0.0/16" # region
    }
  }

  # We can't create a cluster with no node pool defined, but we want to only use
  # separately managed node pools. So we create the smallest possible default
  # node pool and immediately delete it.
  remove_default_node_pool = true
  initial_node_count       = 1

  resource_labels = {
    component = "gke_node",

    # default label
    goog-gke-node = "",
  }

  workload_identity_config {
    workload_pool = "${var.project_id}.svc.id.goog"
  }
}

resource "google_container_node_pool" "gke1_multipurpose01" {
  name       = "multipurpose01"
  cluster    = google_container_cluster.gke1.name
  location   = local.cluster_location

  node_locations = local.cluster_ha ? [
    "${var.region}-${var.zones[0]}",
    "${var.region}-${var.zones[1]}",
    "${var.region}-${var.zones[2]}",
  ] : [
    "${var.region}-${var.zones[0]}"
  ]

  node_count = 1
  
  # wait until >= 1.23.5 reaches to stable channel
  #max_pods_per_node = 220

  node_config {
    preemptible  = false
    machine_type = "e2-standard-4"
    disk_size_gb = 32

    # Intercept metadata server for workloads
    workload_metadata_config {
      mode = "GKE_METADATA"
    }

    # Google recommends custom service accounts that have cloud-platform scope and permissions granted via IAM Roles.
    service_account = google_service_account.gke1_nodes.email
    oauth_scopes    = [
      "https://www.googleapis.com/auth/cloud-platform"
    ]
  }
}

resource "google_container_node_pool" "gke1_preempt1" {
  name       = "preempt1"
  cluster    = google_container_cluster.gke1.name
  location   = local.cluster_location

  # we assume preemptible workloads are non-ha
  node_locations = [
    "${var.region}-${var.zones[0]}"
  ]

  node_count = 0
  
  # wait until >= 1.23.5 reaches to stable channel
  #max_pods_per_node = 220

  node_config {
    preemptible  = true
    machine_type = "e2-standard-2"
    disk_size_gb = 20

    labels = {
      preemptible = true
    }

    taint {
      key    = "preemptible"
      value  = "true"
      effect = "NO_SCHEDULE"
    }

    # Intercept metadata server for workloads
    workload_metadata_config {
      mode = "GKE_METADATA"
    }

    # Google recommends custom service accounts that have cloud-platform scope and permissions granted via IAM Roles.
    service_account = google_service_account.gke1_nodes.email
    oauth_scopes    = [
      "https://www.googleapis.com/auth/cloud-platform"
    ]
  }

  lifecycle {
    ignore_changes = [
      node_config[0].taint
    ]
  }
}
