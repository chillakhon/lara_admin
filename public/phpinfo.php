<?php
phpinfo();
echo "<?php echo 'post_max_size: ' . ini_get('post_max_size') . '<br>'; echo 'upload_max_filesize: ' . ini_get('upload_max_filesize'); ?>" | sudo tee /var/www/laravel/public/check.php
