module.exports = {
  apps: [
    {
      name: 'laravel-queue',
      script: 'artisan',
      interpreter: 'php',
      args: 'queue:work database --sleep=3 --tries=3 --max-time=3600',
      cwd: '/var/www/html/laravel',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      error_file: './storage/logs/queue-error.log',
      out_file: './storage/logs/queue-out.log'
    },
    {
      name: 'laravel-reverb',
      script: 'bash',
      args: '-c "php artisan reverb:start"',
      cwd: '/var/www/html/laravel',
      instances: 1,
      autorestart: true,
      watch: false,
      error_file: './storage/logs/reverb-error.log',
      out_file: './storage/logs/reverb-out.log'
    },
    {
      name: 'laravel-scheduler',
      script: 'scheduler.sh',
      interpreter: 'bash',
      cwd: '/var/www/html/laravel',
      instances: 1,
      autorestart: true,
      watch: false
    },
    {
      name: 'whatsapp-service',
      script: 'index.js',
      cwd: '/var/www/html/laravel/whatsapp-service',
      instances: 1,
      autorestart: true,
      watch: false
    },
    {
      name: 'nuxt-shop',
      script: 'bash',
      args: '-c "node .output/server/index.mjs"',
      cwd: '/var/www/html/nuxt-shop',
      instances: 1,
      autorestart: true,
      watch: false,
      env: {
        PORT: 3000,
        HOST: '127.0.0.1',
        NODE_ENV: 'production'
      }
    }
  ]
};
