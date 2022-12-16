# Least-privilege default GCE account

resource "google_service_account" "gce_default" {
  account_id   = "least-privilege-compute"
  display_name = "Safe SA to use instead of Compute Engine default service account"
  depends_on   = [
    google_project_service.compute,
  ]
}

resource "google_project_iam_member" "gce_default" {
  for_each = toset([
    "roles/logging.logWriter",
    "roles/monitoring.metricWriter",
  ])
  project = var.project_id
  role    = each.value
  member  = "serviceAccount:${google_service_account.gce_default.email}"
}

# Generic ESPv2

resource "google_service_account" "workload_espv2" {
  account_id   = "workload-espv2"
  display_name = "cloud-control: applinth-api"
}

resource "google_project_iam_member" "workload_espv2" {
  for_each = toset([
    "roles/cloudtrace.agent",
    "roles/servicemanagement.serviceController",
  ])
  project = var.project_id
  role    = each.value
  member  = "serviceAccount:${google_service_account.workload_espv2.email}"
}

resource "google_service_account_iam_binding" "workload_espv2" {
  service_account_id = google_service_account.workload_espv2.name

  role = "roles/iam.workloadIdentityUser"

  members = [
    "serviceAccount:${var.project_id}.svc.id.goog[cloud-control/usccp]",
  ]
}


# todo: move these to more specialised substack

# cloud-control: applinth-api

resource "google_service_account" "workload_cc_applinth_api" {
  account_id   = "workload-cc-applinth-api"
  display_name = "cloud-control: applinth-api"
}

resource "google_project_iam_member" "workload_cc_applinth_api" {
  for_each = toset([
    "roles/identityplatform.admin",
    "roles/cloudtrace.agent",
    "roles/servicemanagement.serviceController",
  ])
  project = var.project_id
  role    = each.value
  member  = "serviceAccount:${google_service_account.workload_cc_applinth_api.email}"
}

resource "google_service_account_iam_binding" "workload_cc_applinth_api" {
  service_account_id = google_service_account.workload_cc_applinth_api.name

  role = "roles/iam.workloadIdentityUser"

  members = [
    "serviceAccount:${var.project_id}.svc.id.goog[cloud-control/applinth-api]",
  ]
}
