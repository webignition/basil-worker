host_port = ENV["HOST_PORT"] || 8080

Vagrant.configure("2") do |config|
  config.vm.define "basil_worker" do |basil_worker|
    basil_worker.vm.box = "focal-server-cloudimg-amd64-vagrant"
    basil_worker.vm.box_url = "https://cloud-images.ubuntu.com/focal/current/focal-server-cloudimg-amd64-vagrant.box"

    basil_worker.vm.provider "virtualbox" do |v|
      v.name = "basil_worker"
    end

    basil_worker.vm.network "forwarded_port", guest: 80, host: host_port

    # Copy application files for building image
    basil_worker.vm.provision "file", source: "bin", destination: "~/build/"
    basil_worker.vm.provision "file", source: "composer.json", destination: "~/build/"
    basil_worker.vm.provision "file", source: "composer.lock", destination: "~/build/"
    basil_worker.vm.provision "file", source: "config/bundles.php", destination: "~/build/config/"
    basil_worker.vm.provision "file", source: "config/packages/cache.yaml", destination: "~/build/config/packages/"
    basil_worker.vm.provision "file", source: "config/packages/doctrine.yaml", destination: "~/build/config/packages/"
    basil_worker.vm.provision "file", source: "config/packages/framework.yaml", destination: "~/build/config/packages/"
    basil_worker.vm.provision "file", source: "config/packages/messenger.yaml", destination: "~/build/config/packages/"
    basil_worker.vm.provision "file", source: "config/packages/routing.yaml", destination: "~/build/config/packages/"
    basil_worker.vm.provision "file", source: "config/packages/prod", destination: "~/build/config/packages/prod"
    basil_worker.vm.provision "file", source: "config/routes/annotations.yaml", destination: "~/build/config/routes/"
    basil_worker.vm.provision "file", source: "config/services.yaml", destination: "~/build/config/"
    basil_worker.vm.provision "file", source: "public/index.php", destination: "~/build/public/"
    basil_worker.vm.provision "file", source: "src", destination: "~/build/"
    basil_worker.vm.provision "file", source: "Dockerfile", destination: "~/build/"
    basil_worker.vm.provision "file", source: "./supervisor", destination: "~/build/"

    # Copy system files and provision for use
    basil_worker.vm.provision "file", source: "./.docker-compose.env", destination: "~/.docker-compose.env"
    basil_worker.vm.provision "file", source: "./docker-compose.yml", destination: "~/docker-compose.yml"
    basil_worker.vm.provision "file", source: "./nginx/Dockerfile", destination: "~/nginx/Dockerfile"
    basil_worker.vm.provision "file", source: "./nginx/site.conf", destination: "~/nginx/site.conf"
    basil_worker.vm.provision "shell", path: "provision.sh", env: {"APP_BUILD_CONTEXT" => "/home/vagrant/build", "LOCAL_SOURCE_PATH" => "/var/basil/source"}

    # Copy docker services self-test files and run docker services self-test process
    basil_worker.vm.provision "file", source: "./self-test/fixtures", destination: "~/self-test/fixtures"
    basil_worker.vm.provision "shell", path: "self-test/docker-compose-services.sh"
    basil_worker.vm.provision "shell", path: "self-test/delegator.sh", env: {"BROWSER" => "chrome"}
    basil_worker.vm.provision "shell", path: "self-test/delegator.sh", env: {"BROWSER" => "firefox"}

    # Copy app self-test files and run app self-test process
    basil_worker.vm.provision "file", source: "./self-test/app/composer.json", destination: "~/self-test/app/composer.json"
    basil_worker.vm.provision "file", source: "./self-test/app/src", destination: "~/self-test/app/src"
    basil_worker.vm.provision "file", source: "./self-test/services.yml", destination: "~/self-test/services.yml"

    basil_worker.vm.provision "shell", path: "self-test/app.sh"
  end
end
