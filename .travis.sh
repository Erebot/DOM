#!/bin/sh

export DEBIAN_FRONTEND=noninteractive
apt-get remove -qq hhvm
add-apt-repository -y ppa:mapnik/boost
wget -O- http://dl.hhvm.com/conf/hhvm.gpg.key | apt-key add -
echo deb http://dl.hhvm.com/ubuntu precise main | tee /etc/apt/sources.list.d/hhvm.list
apt-get update -q
apt-get install -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" hhvm-nightly
