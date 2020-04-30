#!/bin/bash
cur_dir=`old=\`pwd\`; cd \`dirname $0\`; echo \`pwd\`; cd $old;`
prj=`basename $cur_dir`
env=$1

if [ -z "$env" ]; then
	echo "Usage: $0 ENV"
	echo "    ENV: dev, online"
	exit 1
fi

echo ""
echo "#######################################"
echo "Project: $prj"
echo "Env    : $env"
echo ""

prj_dir=/data/www/$prj

echo ""
echo "git pull ⬇"
git pull

echo ""
echo -n "执行 composer update?(y/n)[n] "
read yn
if [ "$yn" = "y" ] ; then
  echo ""
  echo "composer update⬇"
  composer update
else
  echo "skip composer update"
fi

function deploy_dev()
{
	mkdir -p /data/applogs/$prj
	chmod ugo+rwx /data/applogs /data/applogs/$prj

  rm .env
	ln -sf $prj_dir/test.env $prj_dir/.env

	mkdir -p /var/log/nginx
	ln -sf $prj_dir/deploy/conf/dev/www.video.conf /usr/local/nginx/conf/vhost/www.video.conf
	ln -sf $prj_dir/deploy/conf/dev/admin.video.conf /usr/local/nginx/conf/vhost/admin.video.conf
	ln -sf $prj_dir/deploy/conf/dev/s1.video.conf /usr/local/nginx/conf/vhost/s1.video.conf
	ln -sf $prj_dir/deploy/conf/dev/api.video.conf /usr/local/nginx/conf/vhost/api.video.conf
	ln -sf $prj_dir/deploy/conf/dev/m.video.conf /usr/local/nginx/conf/vhost/m.video.conf
	ln -sf $prj_dir/deploy/conf/dev/utcc.video.conf /usr/local/nginx/conf/vhost/utcc.video.conf
}

function deploy_online()
{
	mkdir -p /data/applogs/$prj
	chmod ugo+rwx /data/applogs /data/applogs/$prj

  rm .env
	ln -sf $prj_dir/online.env $prj_dir/.env

	mkdir -p /var/log/nginx
	ln -sf $prj_dir/deploy/conf/online/www.video.conf /usr/local/nginx/conf/vhost/www.video.conf
	ln -sf $prj_dir/deploy/conf/online/admin.video.conf /usr/local/nginx/conf/vhost/admin.video.conf
	ln -sf $prj_dir/deploy/conf/online/s1.video.conf /usr/local/nginx/conf/vhost/s1.video.conf
	ln -sf $prj_dir/deploy/conf/online/api.video.conf /usr/local/nginx/conf/vhost/api.video.conf
	ln -sf $prj_dir/deploy/conf/online/m.video.conf /usr/local/nginx/conf/vhost/m.video.conf
}

# update assets.json
cd $prj_dir && php $prj_dir/assets_md5.php static template
if [ $? -eq 0 ]; then
	echo ""
	echo "update assets.json done."
	echo ""
else
	echo ""
	echo "update assets.json fail! please resolve it!"
	echo ""
fi
cd $cur_dir
# end update assets.json

echo ""
echo "#######################################"
echo "Project: $prj"
echo "Env    : $env"
echo ""

if [ "$env" = "online" ]; then
	deploy_online
else
	deploy_dev
fi

$prj_dir/deploy/server.sh restart

echo ""
echo "done."
echo "#######################################"
echo ""
