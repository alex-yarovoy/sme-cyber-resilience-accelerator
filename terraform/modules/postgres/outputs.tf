output "database_endpoint" {
  value       = "postgres.example.internal:5432"
  description = "Database connection endpoint exposed to consuming modules."
}

output "database_name" {
  value = var.database_name
}
