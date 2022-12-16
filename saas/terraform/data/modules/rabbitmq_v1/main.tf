data "google_service_account" "gce_default" {
  account_id = "least-privilege-compute"
}

resource "google_compute_instance" "default" {
  name         = var.name
  machine_type = var.machine_type
  zone         = var.zone

  tags = ["rabbitmq"]

  boot_disk {
    auto_delete = false

    initialize_params {
      image = "debian-cloud/debian-11"
      size  = "50"
      type  = "pd-balanced"
    }
  }

  network_interface {
    network    = "default"
    subnetwork = "data"
  }

  service_account {
    email  = data.google_service_account.gce_default.email
    scopes = ["cloud-platform"]
  }

  labels = {
    component = "rabbitmq"
  }
}