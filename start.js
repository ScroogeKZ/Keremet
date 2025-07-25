#!/usr/bin/env node
const { spawn } = require('child_process');

console.log('Starting PHP development server...');

// Pass environment variables to PHP
const php = spawn('php', ['-S', '0.0.0.0:5000'], {
    cwd: 'public',
    stdio: 'inherit',
    env: {
        ...process.env,
        DATABASE_URL: process.env.DATABASE_URL,
        PGHOST: process.env.PGHOST,
        PGPORT: process.env.PGPORT,
        PGUSER: process.env.PGUSER,
        PGPASSWORD: process.env.PGPASSWORD,
        PGDATABASE: process.env.PGDATABASE
    }
});

php.on('close', (code) => {
    console.log(`PHP server exited with code ${code}`);
});

process.on('SIGINT', () => {
    console.log('Shutting down PHP server...');
    php.kill();
    process.exit();
});