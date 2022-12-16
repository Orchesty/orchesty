data "google_compute_default_service_account" "default" {}

resource "google_cloud_scheduler_job" "certbot" {
  name             = "certbot"
  description      = "Periodic certificate renewal"
  schedule         = "00 9 * * FRI"
  time_zone        = "Europe/Prague"
  attempt_deadline = "30s"

  retry_config {
    retry_count = 0
  }

  http_target {
    http_method = "POST"
    uri         = "https://${var.region}-run.googleapis.com/apis/run.googleapis.com/v1/namespaces/${var.project_id}/jobs/certbot:run"
    body        = ""
    oauth_token {
      service_account_email = data.google_compute_default_service_account.default.email
    }
  }
}
