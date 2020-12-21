Vagrant.configure("2") do |config|
  config.vm.define "basil_worker" do |basil_worker|
    basil_worker.vm.box = "focal-server-cloudimg-amd64-vagrant"
    basil_worker.vm.box_url = "https://cloud-images.ubuntu.com/focal/current/focal-server-cloudimg-amd64-vagrant.box"

    basil_worker.vm.provider "virtualbox" do |v|
      v.name = "basil_worker"
    end

    basil_worker.vm.provision "file", source: "./.docker-compose.env", destination: "~/.docker-compose.env"
    basil_worker.vm.provision "file", source: "./docker-compose.yml", destination: "~/docker-compose.yml"
    basil_worker.vm.provision "file", source: "./nginx/Dockerfile", destination: "~/nginx/Dockerfile"
    basil_worker.vm.provision "file", source: "./nginx/site.conf", destination: "~/nginx/site.conf"
    basil_worker.vm.provision "shell", path: "provision.sh"
  end
end
