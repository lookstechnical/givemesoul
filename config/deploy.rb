require 'capistrano/ext/multistage'

set :application, "lucid-design.net"
set :repository,  "git@github.com:lookstechnical/givemesoul.git"
set :scm_passphrase, "ciaran"

set  :keep_releases,  3

set :stages, ["dev", "production"]
set :default_stage, "production"

namespace :deploy do
  namespace :assets do
    task :precompile, :roles => :web, :except => { :no_release => true } do
      from = source.next_revision(current_revision)
      if capture("cd #{latest_release} && #{source.local.log(from)} vendor/assets/ app/assets/ | wc -l").to_i > 0
        run %Q{cd #{latest_release} && #{rake} RAILS_ENV=#{rails_env} #{asset_env} assets:precompile}
      else
        logger.info "Skipping asset pre-compilation because there were no asset changes"
      end
    end
  end
end