provider "aws" {
  region = var.aws_region
}

resource "aws_s3_bucket" "datalake" {
  bucket        = "hack26-s3-datalake-${var.team_name}"
  force_destroy = true 
}

resource "aws_s3_bucket_versioning" "datalake_versioning" {
  bucket = aws_s3_bucket.datalake.id
  versioning_configuration {
    status = "Enabled"
  }
}

data "aws_vpc" "default" {
  default = true
}

data "aws_subnets" "default" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }
}

resource "aws_security_group" "alb_sg" {
  name        = "hack26-alb-sg"
  description = "Allow inbound HTTP traffic to ALB"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_security_group" "ec2_sg" {
  name        = "hack26-ec2-sg"
  description = "Allow inbound traffic from ALB to EC2 instances"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb_sg.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_lb" "app_alb" {
  name               = "hack26-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]
  subnets            = data.aws_subnets.default.ids
}

resource "aws_lb_target_group" "app_tg" {
  name        = "hack26-tg"
  port        = 80
  protocol    = "HTTP"
  vpc_id      = data.aws_vpc.default.id
  target_type = "instance"

  health_check {
    path                = "/"
    healthy_threshold   = 2
    unhealthy_threshold = 10
  }
}

resource "aws_lb_listener" "app_listener" {
  load_balancer_arn = aws_lb.app_alb.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.app_tg.arn
  }
}

resource "aws_launch_template" "app_server" {
  name_prefix   = "hack26-app-"
  image_id      = "ami-0c101f26f147fa7fd"
  instance_type = "t3.micro"

  iam_instance_profile {
    name = "LabInstanceProfile"
  }

  vpc_security_group_ids = [aws_security_group.ec2_sg.id]

  user_data = base64encode(<<-EOF
              #!/bin/bash
              yum update -y
              yum install docker -y
              service docker start
              usermod -a -G docker ec2-user
              
              aws ecr get-login-password --region ${var.aws_region} | docker login --username AWS --password-stdin 042778646119.dkr.ecr.${var.aws_region}.amazonaws.com
              
              cat <<EOT > /.env
              APP_ENV=production
              APP_KEY=base64:AjRMsdd2vQJSbEHi3+iozQs4hKCPJIr38YfqCKjhyiU=
              APP_DEBUG=false
              DB_CONNECTION=pgsql
              DB_HOST=hack26-database.c9ecgg0cemqq.us-east-1.rds.amazonaws.com
              DB_PORT=5432
              DB_DATABASE=postgres
              DB_USERNAME=postgres
              DB_PASSWORD=DBHack2026!!
              SESSION_DRIVER=cookie
              EOT

              docker run -d -p 80:80 --env-file /.env --restart unless-stopped 042778646119.dkr.ecr.${var.aws_region}.amazonaws.com/portal-sanitari:latest
              EOF
  )
}

resource "aws_autoscaling_group" "app_asg" {
  vpc_zone_identifier = data.aws_subnets.default.ids
  target_group_arns   = [aws_lb_target_group.app_tg.arn]

  desired_capacity = 1
  min_size         = 1
  max_size         = 3

  launch_template {
    id      = aws_launch_template.app_server.id
    version = "$Latest"
  }
}

resource "aws_autoscaling_policy" "app_cpu_policy" {
  name                   = "hack26-cpu-scale-out"
  autoscaling_group_name = aws_autoscaling_group.app_asg.name
  policy_type            = "TargetTrackingScaling"

  target_tracking_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ASGAverageCPUUtilization"
    }
    target_value = 70.0
  }
}

resource "aws_security_group" "ai_sg" {
  name        = "hack26-ai-sg"
  description = "Security group for AI Inference Server"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port   = 5000
    to_port     = 5000
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 11434
    to_port     = 11434
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_instance" "ai_server" {
  ami           = "ami-0c101f26f147fa7fd"
  instance_type = "t3.large"
  
  iam_instance_profile = "LabInstanceProfile"
  vpc_security_group_ids = [aws_security_group.ai_sg.id]

  root_block_device {
    volume_size = 30
    volume_type = "gp3"
  }

  user_data = <<-EOF
              #!/bin/bash
              yum update -y
              yum install git docker python3 pip -y
              service docker start
              usermod -a -G docker ec2-user
              
              docker run -d -v ollama:/root/.ollama -p 11434:11434 --restart unless-stopped --name ollama ollama/ollama
              
              docker exec ollama ollama pull llama3
              EOF

  tags = {
    Name = "Hack26-AI-Inference-Server"
  }
}
