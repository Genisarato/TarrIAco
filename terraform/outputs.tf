output "datalake_bucket_name" {
  description = "S3 Data Lake bucket name"
  value       = aws_s3_bucket.datalake.id
}

output "alb_dns_name" {
  description = "Public ALB DNS Name"
  value       = aws_lb.app_alb.dns_name
}

output "ai_server_public_ip" {
  description = "AI Server Public IP"
  value       = aws_instance.ai_server.public_ip
}
