resource "google_artifact_registry_repository" "infra" {
  location      = var.region
  repository_id = "infrastructure"
  description   = "Infrastructrure images"
  format        = "DOCKER"
  depends_on = [
    google_project_service.artifactregistry
  ]
}

resource "google_artifact_registry_repository" "general" {
  location      = var.region
  repository_id = "general"
  description   = "General Docker Repository"
  format        = "DOCKER"
  depends_on = [
    google_project_service.artifactregistry
  ]
}

resource "google_artifact_registry_repository" "orchesty" {
  location      = var.region
  repository_id = "orchesty"
  description   = "Orchesty Docker Repository"
  format        = "DOCKER"
  depends_on = [
    google_project_service.artifactregistry
  ]
}
