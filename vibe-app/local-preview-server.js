const http = require('http');
const fs = require('fs');
const path = require('path');

const host = '127.0.0.1';
const port = 8000;
const previewPath = path.join(__dirname, 'preview.html');
const publicPath = path.join(__dirname, 'public');
const mimeTypes = {
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.avif': 'image/avif',
  '.json': 'application/json; charset=utf-8',
};

const server = http.createServer((req, res) => {
  if (req.method === 'POST' && req.url === '/generate') {
    res.writeHead(200, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('Generated successfully');
    return;
  }

  if (req.method === 'GET' && (req.url.startsWith('/images/') || req.url.startsWith('/css/') || req.url.startsWith('/js/') || req.url.startsWith('/data/'))) {
    const requested = path.normalize(decodeURIComponent(req.url.split('?')[0])).replace(/^([/\\])+/, '');
    const filePath = path.join(publicPath, requested);

    if (!filePath.startsWith(publicPath)) {
      res.writeHead(403, { 'Content-Type': 'text/plain; charset=utf-8' });
      res.end('Forbidden');
      return;
    }

    fs.readFile(filePath, (error, file) => {
      if (error) {
        res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('Asset not found.');
        return;
      }

      res.writeHead(200, { 'Content-Type': mimeTypes[path.extname(filePath).toLowerCase()] || 'application/octet-stream' });
      res.end(file);
    });
    return;
  }

  fs.readFile(previewPath, 'utf8', (error, html) => {
    if (error) {
      res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
      res.end('Preview file not found.');
      return;
    }

    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(html);
  });
});

server.listen(port, host, () => {
  console.log(`Vibe App preview running at http://${host}:${port}`);
});
