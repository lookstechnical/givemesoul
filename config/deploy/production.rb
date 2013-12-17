server "78.109.168.52", :app, :web, :db, :primary => true
set :deploy_to, "/var/www/vhosts/testing.givemesoul.com/htdocs/"


set :branch, 'master'

set :deploy_via, :remote_cache
set :keep_releases,  1

after "deploy", "update_configs"

task :update_configs, :roles => :app do
	sudo "mv #{release_path}/config/site.php #{release_path}/config/site.php.local"
	sudo "mv #{release_path}/config/site.php.prod #{release_path}/config/site.php"
	sudo "rm -R #{release_path}/files"
    sudo "ln -s #{shared_path}/files  #{release_path}/files"
	
	sudo "chmod 777 -R #{release_path}/files"
end