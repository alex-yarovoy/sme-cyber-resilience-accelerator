variable "environment" {
  type        = string
  description = "Deployment environment label."
  default     = "example"
}

module "network" {
  source = "../network"
}

module "postgres" {
  source        = "../postgres"
  database_name = "identity_mfa_${var.environment}"
}

module "backups" {
  source      = "../object_storage"
  bucket_name = "identity-mfa-backups-${var.environment}"
}

output "identity_mfa_summary" {
  value = {
    environment       = var.environment
    network_cidr      = module.network.network_cidr
    database_endpoint = module.postgres.database_endpoint
    backup_bucket     = module.backups.bucket_name
  }
}
