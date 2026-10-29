output "network_cidr" {
  value       = var.cidr_block
  description = "Network CIDR passed to provider-specific modules."
}

output "private_subnet_ids" {
  value       = ["subnet-a", "subnet-b"]
  description = "Subnet identifiers exposed to downstream modules."
}
