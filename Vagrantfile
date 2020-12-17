Vagrant.configure("2") do |config|
  config.vm.define "basil-worker"
  config.vm.box = "focal-server-cloudimg-amd64-vagrant"
  config.vm.box_url = "https://cloud-images.ubuntu.com/focal/current/focal-server-cloudimg-amd64-vagrant.box"

  config.vm.provider "virtualbox" do |v|
    v.name = "basil-worker"
  end
end
