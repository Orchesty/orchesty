resource "google_service_account" "certbot" {
  account_id   = "certbot"
  display_name = "Certbot in CloudRun"
}

resource "google_project_iam_member" "certbot" {
  for_each = toset([
    "roles/compute.admin",
    "roles/dns.admin",
    "roles/storage.objectAdmin",
  ])
  project = var.project_id
  role    = each.value
  member  = "serviceAccount:${google_service_account.certbot.email}"
}
