terraform {
  required_version = ">= 1.5.0"
}

module "identity_mfa" {
  source      = "../../modules/identity_mfa"
  environment = "example"
}

output "identity_mfa" {
  value = module.identity_mfa.identity_mfa_summary
}
