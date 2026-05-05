module.exports = {
  apps: [
    // Laravel Queue Worker
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

    // Laravel Reverb (WebSockets)
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

    // Laravel Scheduler
    {
      name: 'laravel-scheduler',
      script: 'scheduler.sh',
      interpreter: 'bash',
      cwd: '/var/www/html/laravel',
      instances: 1,
      autorestart: true,
      watch: false
    },

    // WhatsApp Service (если это часть Laravel проекта)
    {
      name: 'whatsapp-service',
      script: 'index.js',
      cwd: '/var/www/html/laravel/whatsapp-service',
      instances: 1,
      autorestart: true,
      watch: false
    }
  ]
};
