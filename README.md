# Acuma.in
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/e-spres-oh/acuma.in/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/e-spres-oh/acuma.in/?branch=master)

## Setup

In order to run the project you VirtualBox/vagrant installed locally, and set 10.10.10.10 to acuma.local in your hosts file.
After you've booted the machine with `vagrant up` you can set up the database.

```sh
$ vagrant ssh
[vagrant@localhost ~]$ cd /vagrant
[vagrant@localhost vagrant]$ make schema
```

