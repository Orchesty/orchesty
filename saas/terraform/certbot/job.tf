resource "google_cloud_run_v2_job" "certbot" {
  name         = "certbot"
  location     = var.region
  launch_stage = "BETA"

  template {
    template {
      timeout     = "600s"
      max_retries = 0

      service_account = google_service_account.certbot.email

      containers {
        image = var.image
        #args  = ["certonly.sh"]
        args = ["renew.sh"]
        env {
          name  = "BUCKET"
          value = google_storage_bucket.certbot.name
        }
        env {
          name  = "GC_PROXY_NAME"
          value = var.proxy_name
        }
        env {
          name  = "INFIX"
          value = var.domain_infix
        }
        env {
          name  = "SKIP_DEPLOYMENT"
          value = var.bootstrap || var.skip_deployment ? "yes" : ""
        }
      }
    }
  }
}
