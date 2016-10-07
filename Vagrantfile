# -*- mode: ruby -*-
# vi: set ft=ruby :

$script = <<SCRIPT
if [ ! -f /etc/vagrant_provisioned.6 ]; then
  if [ ! -f /etc/vagrant_provisioned.5 ]; then
    if [ ! -f /etc/vagrant_provisioned.4 ]; then
      if [ ! -f /etc/vagrant_provisioned.3 ]; then
        if [ ! -f /etc/vagrant_provisioned.2 ]; then
          if [ ! -f /etc/vagrant_provisioned.1 ]; then
            sudo dnf install -y php mariadb mariadb-server
            systemctl start mariadb
            systemctl enable mariadb
            touch /etc/vagrant_provisioned.1
          fi
          dnf update -y httpd libnghttp2
          dnf install -y polkit
          sed -i 's/^User apache/User vagrant/g' /etc/httpd/conf/httpd.conf
          sed -i 's@^;date.timezone =@date.timezone = Europe/Bucharest@g' /etc/php.ini
          rm -rf /var/www/html
          ln -s /vagrant/web /var/www/html
          systemctl enable httpd
          touch /etc/vagrant_provisioned.2
        fi
        cd /tmp
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php -r "if (hash_file('SHA384', 'composer-setup.php') === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
        php composer-setup.php
        php -r "unlink('composer-setup.php');"
        mv composer.phar /usr/local/bin/
        touch /etc/vagrant_provisioned.3
      fi
      dnf install -y php-mysql php-zip
    fi
    cat <<EOQ > /etc/httpd/conf.d/vhosts.conf
<VirtualHost *:80>
  ServerName acuma.local
  ServerAdmin webmaster@localhost
  DocumentRoot /vagrant/web

  <Directory "/vagrant/web">
    AllowOverride All
    Require all granted
    Options +Indexes

    FileETag none
  </Directory>
</VirtualHost>
EOQ
  fi
  sed -i 's/^EnableSendfile on/EnableSendfile Off/g' /etc/httpd/conf/httpd.conf
fi
if [ ! -f /etc/vagrant_provisioned.7 ]; then
  dnf install -y git
  touch /etc/vagrant_provisioned.7
fi
systemctl start httpd
crontab /vagrant/crontab_file.txt
SCRIPT

Vagrant.configure(2) do |config|
  config.vm.box = "box-cutter/fedora23"

  config.vm.network "private_network", ip: "10.10.10.10"

  config.vm.provision "shell", inline: $script, run: "always"
  config.ssh.insert_key = false
end
