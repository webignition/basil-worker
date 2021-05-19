host_port = ENV["HOST_PORT"] || 8080

Vagrant.configure("2") do |config|
  config.vm.define "basil_worker" do |basil_worker|
    basil_worker.vm.box = "focal-server-cloudimg-amd64-vagrant"
    basil_worker.vm.box_url = "https://cloud-images.ubuntu.com/focal/current/focal-server-cloudimg-amd64-vagrant.box"

    basil_worker.vm.provider "virtualbox" do |v|
      v.name = "basil_worker"
    end

    basil_worker.vm.network "forwarded_port", guest: 80, host: host_port

    # Copy system files and provision for use
    basil_worker.vm.provision "file", source: "./.docker-compose.env", destination: "~/.docker-compose.env"
    basil_worker.vm.provision "file", source: "./docker-compose.yml", destination: "~/docker-compose.yml"
    basil_worker.vm.provision "file", source: "./nginx/Dockerfile", destination: "~/nginx/Dockerfile"
    basil_worker.vm.provision "file", source: "./nginx/site.conf", destination: "~/nginx/site.conf"
    basil_worker.vm.provision "shell", path: "provision.sh"

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
