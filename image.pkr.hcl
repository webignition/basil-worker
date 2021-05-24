
variable "digitalocean_api_token" {
  type      = string
  sensitive = true
}

variable "snapshot_name" {
  type = string
}

source "digitalocean" "worker_base" {
  api_token     = "${var.digitalocean_api_token}"
  image         = "ubuntu-20-04-x64"
  region        = "lon1"
  size          = "s-1vcpu-1gb"
  snapshot_name = "basil-worker-${var.snapshot_name}"
  ssh_username  = "root"
}

build {
  sources = ["source.digitalocean.worker_base"]

  # Copy application files for building image
  provisioner "shell" {
    inline = ["mkdir -p ~/build"]
  }

  provisioner "file" {
    destination = "~/build/bin"
    source      = "bin"
  }

  provisioner "file" {
    destination = "~/build/bin"
    source      = "composer.json"
  }

  provisioner "file" {
    destination = "~/build/bin"
    source      = "composer.lock"
  }

  provisioner "shell" {
    inline = ["mkdir -p ~/build/config"]
  }

  provisioner "file" {
    destination = "~/build/config"
    source      = "config/bundles.php"
  }

  provisioner "shell" {
    inline = ["mkdir -p ~/build/config/packages"]
  }

  provisioner "file" {
    destination = "~/build/config/packages"
    source      = "config/packages/cache.yaml"
  }

  provisioner "file" {
    destination = "~/build/config/packages"
    source      = "config/packages/doctrine.yaml"
  }

  provisioner "file" {
    destination = "~/build/config/packages"
    source      = "config/packages/doctrine_migrations.yaml"
  }

  provisioner "file" {
    destination = "~/build/config/packages"
    source      = "config/packages/framework.yaml"
  }

  provisioner "file" {
    destination = "~/build/config/packages"
    source      = "config/packages/messenger.yaml"
  }

  provisioner "file" {
    destination = "~/build/config/packages"
    source      = "config/packages/routing.yaml"
  }

  provisioner "file" {
    destination = "~/build/config/packages/prod"
    source      = "config/packages/prod"
  }

  provisioner "shell" {
    inline = ["mkdir -p ~/build/config/routes"]
  }

  provisioner "file" {
    destination = "~/build/config/routes"
    source      = "config/routes/annotations.yaml"
  }

  provisioner "file" {
    destination = "~/build/config"
    source      = "config/services.yaml"
  }

  provisioner "shell" {
    inline = ["mkdir -p ~/build/public"]
  }

  provisioner "file" {
    destination = "~/build/public"
    source      = "public/index.php"
  }

  provisioner "file" {
    destination = "~/build"
    source      = "src"
  }

  provisioner "file" {
    destination = "~/build"
    source      = "migrations"
  }

  provisioner "file" {
    destination = "~/build"
    source      = "Dockerfile"
  }

  # Copy system files and provision for use
  provisioner "file" {
    destination = "~/docker-compose.yml"
    source      = "docker-compose.yml"
  }

  provisioner "file" {
    destination = "~/.docker-compose.env"
    source      = ".docker-compose.env"
  }

  provisioner "shell" {
    inline = ["mkdir -p ~/nginx"]
  }

  provisioner "file" {
    destination = "~/nginx/Dockerfile"
    source      = "nginx/Dockerfile"
  }

  provisioner "file" {
    destination = "~/nginx/site.conf"
    source      = "nginx/site.conf"
  }

  provisioner "shell" {
    scripts = ["./provision.sh"]
  }

  # Copy docker services self-test files and run docker services self-test process
  provisioner "shell" {
    inline = ["mkdir -p ~/self-test"]
  }

  provisioner "file" {
    destination = "~/self-test/fixtures"
    source      = "self-test/fixtures"
  }

  provisioner "shell" {
    scripts = ["./self-test/docker-compose-services.sh"]
  }

  provisioner "shell" {
    environment_vars = ["BROWSER=chrome"]
    scripts          = ["./self-test/delegator.sh"]
  }

  provisioner "shell" {
    environment_vars = ["BROWSER=firefox"]
    scripts          = ["./self-test/delegator.sh"]
  }

  # Copy app self-test files and run app self-test process
  provisioner "shell" {
    inline = ["mkdir -p ~/self-test/app"]
  }

  provisioner "file" {
    destination = "~/self-test/app/composer.json"
    source      = "self-test/app/composer.json"
  }

  provisioner "file" {
    destination = "~/self-test/app/src"
    source      = "self-test/app/src"
  }

  provisioner "file" {
    destination = "~/self-test/services.yml"
    source      = "self-test/services.yml"
  }

  provisioner "shell" {
    scripts = ["./self-test/app.sh"]
  }

}
