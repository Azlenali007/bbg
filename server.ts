import express, { Request, Response } from 'express';
import http from 'http';
import { spawn } from 'child_process';
import path from 'path';

const app = express();
const PORT = 3000;
const PHP_PORT = 8081;
const PHP_HOST = '127.0.0.1';

// Ensure MariaDB is running
try {
  const checkMaria = spawn('mysqladmin', ['ping']);
  checkMaria.on('close', (code) => {
    if (code !== 0) {
      spawn('sh', ['-c', 'mkdir -p /run/mysqld && chown -R mysql:mysql /run/mysqld /var/lib/mysql && /usr/bin/mysqld_safe --datadir=/var/lib/mysql --user=mysql >/var/log/mysql.log 2>&1 &'], { stdio: 'ignore' });
    }
  });
} catch {
  // Ignore
}

// Start PHP built-in server on 127.0.0.1:8080
const phpProcess = spawn('php', ['-S', `${PHP_HOST}:${PHP_PORT}`, 'router.php'], {
  cwd: process.cwd(),
  stdio: 'inherit',
});

phpProcess.on('error', (err) => {
  console.error('Failed to spawn PHP process:', err);
});

// Proxy all requests directly to the PHP built-in server
app.use((req: Request, res: Response) => {
  const options: http.RequestOptions = {
    hostname: PHP_HOST,
    port: PHP_PORT,
    path: req.url,
    method: req.method,
    headers: {
      ...req.headers,
      host: req.headers.host || `localhost:${PORT}`,
      'x-forwarded-for': req.socket.remoteAddress || '127.0.0.1',
      'x-forwarded-proto': req.protocol,
    },
  };

  const proxyReq = http.request(options, (proxyRes) => {
    res.writeHead(proxyRes.statusCode || 200, proxyRes.headers);
    proxyRes.pipe(res, { end: true });
  });

  proxyReq.on('error', (err) => {
    console.error('PHP Proxy error:', err.message);
    if (!res.headersSent) {
      res.status(502).send('<html><body style="font-family:sans-serif;padding:40px;text-align:center;"><h2>Starting SMM Engine...</h2><p>Please refresh in a few seconds.</p></body></html>');
    }
  });

  req.pipe(proxyReq, { end: true });
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`ApexSMM Server listening on port ${PORT}`);
});
