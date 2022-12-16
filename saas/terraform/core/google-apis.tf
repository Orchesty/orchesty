resource "google_project_service" "compute" {
  service = "compute.googleapis.com"
}

resource "google_project_service" "dns" {
  service = "dns.googleapis.com"
}

resource "google_project_service" "container" {
  service = "container.googleapis.com"
}

resource "google_project_service" "servicecontrol" {
  service = "servicecontrol.googleapis.com"
}

resource "google_project_service" "cloudtrace" {
  service = "cloudtrace.googleapis.com"
}

resource "google_project_service" "artifactregistry" {
  service = "artifactregistry.googleapis.com"
}
