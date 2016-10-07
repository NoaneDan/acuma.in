include config/environment.mk
GIT_REVISION_DETECTED = $(shell git rev-parse HEAD)
VERSION ?= master

common-configuration:
	cp config/template/auto.environment.php config/auto.environment.php
	sed -i 's/MYSQL_USERNAME/"$(MYSQL_USERNAME)"/g' config/auto.environment.php
	sed -i 's/MYSQL_PASSWORD/"$(MYSQL_PASSWORD)"/g' config/auto.environment.php
	sed -i 's/MYSQL_DATABASE/"$(MYSQL_DATABASE)"/g' config/auto.environment.php
	sed -i 's/INSTAGRAM_CLIENT_ID/"$(INSTAGRAM_CLIENT_ID)"/g' config/auto.environment.php
	sed -i 's/INSTAGRAM_CLIENT_SECRET/"$(INSTAGRAM_CLIENT_SECRET)"/g' config/auto.environment.php
	sed -i 's/FACEBOOK_CLIENT_ID/"$(FACEBOOK_CLIENT_ID)"/g' config/auto.environment.php
	sed -i 's/FACEBOOK_CLIENT_SECRET/"$(FACEBOOK_CLIENT_SECRET)"/g' config/auto.environment.php
	sed -i 's/TWITTER_CONSUMER_KEY/"$(TWITTER_CONSUMER_KEY)"/g' config/auto.environment.php
	sed -i 's/TWITTER_CONSUMER_SECRET/"$(TWITTER_CONSUMER_SECRET)"/g' config/auto.environment.php
	sed -i 's/MINIMIZE_CSS/$(MINIMIZE_CSS)/g' config/auto.environment.php
	sed -i 's/MINIMIZE_JS/$(MINIMIZE_JS)/g' config/auto.environment.php

local-configuration: common-configuration
	sed -i 's/GIT_REVISION/"$(GIT_REVISION)"/g' config/auto.environment.php
	sed -i 's/DEBUG_MODE/true/g' config/auto.environment.php

schema:
	cat sql/access_tokens.sql sql/city.sql sql/user_table.sql sql/twitter_user.sql sql/timeline_table.sql sql/twitter_tweet.sql sql/twitter_post.sql sql/fb_location.sql sql/fb_event.sql sql/fb_photo.sql sql/instagram_location.sql sql/instagram_post.sql sql/moderation_table.sql sql/report_table.sql |\
	mysql -u $(MYSQL_USERNAME) $(shell if [ "" != "$(MYSQL_PASSWORD)" ]; then echo -p$(MYSQL_PASSWORD); fi) $(MYSQL_DATABASE)

production-latest-code:
	git fetch --all -p
	git reset --hard origin/$(VERSION)
	composer.phar install

production-update: production-latest-code common-configuration
	sed -i 's/GIT_REVISION/"$(GIT_REVISION_DETECTED)"/g' config/auto.environment.php
	sed -i 's/DEBUG_MODE/false/g' config/auto.environment.php

.PHONY: common-configuration local-configuration schema production-update
