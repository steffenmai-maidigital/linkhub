# Running LinkHub behind a reverse proxy (HTTPS)

*Deutsche Fassung: [../reverse-proxy.md](../reverse-proxy.md)*

Inside the container LinkHub only speaks HTTP on port 80. In production a
**reverse proxy** accepts requests over HTTPS and passes them on. This guide
explains what matters and shows the setup for the common proxies.

## The three rules

1. **Set `APP_URL` to the public address** – with `https://` and without a
   trailing slash:

   ```yaml
   APP_URL: "https://links.firma.de"
   ```

   Laravel builds every link to stylesheets, images and forms from it. If it
   still contains the internal address, the page arrives in the browser but
   without styling. If `APP_URL` starts with `https://`, LinkHub generates all
   links with https – even if the proxy reports "http" internally.

2. **Pass the headers on:** the proxy must send `Host`, `X-Forwarded-For` and
   above all **`X-Forwarded-Proto`** to LinkHub. Most proxies do so by default.

3. **Trust the proxy:** LinkHub only believes these headers from proxies listed
   in `TRUSTED_PROXIES`. The default is the internal networks
   (`127.0.0.1, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16`). If the proxy runs
   in the same Docker network or the company network, that is enough.
   Otherwise enter its address, e.g. `TRUSTED_PROXIES: "203.0.113.10"`.

After every change to the Compose file run `docker compose up -d` (Synology:
rebuild the project).

## Synology DSM

Control Panel → **Login Portal → Advanced → Reverse Proxy** → **Create**:

| Field | Value |
|---|---|
| Source: protocol / hostname / port | HTTPS / `links.firma.de` / 443 |
| Destination: protocol / hostname / port | HTTP / `localhost` / 7280 |
| Custom Header | **Create → WebSocket** |

Assign the certificate under Control Panel → **Security → Certificate →
Settings**. Step by step: [synology.md](synology.md#5-https-via-the-dsm-reverse-proxy).

## Nginx Proxy Manager

**Hosts → Proxy Hosts → Add Proxy Host:**

- *Domain Names:* `links.firma.de`
- *Scheme:* `http`, *Forward Hostname / IP:* address of the server or NAS,
  *Forward Port:* `7280`
- switch on *Block Common Exploits*
- tab **SSL:** choose or request a certificate, switch on **Force SSL** and
  **HTTP/2**
- tab **Advanced** (for uploads): `client_max_body_size 4m;`

Nginx Proxy Manager sets the required headers itself.

## nginx

```nginx
server {
    listen 443 ssl http2;
    server_name links.firma.de;

    ssl_certificate     /etc/letsencrypt/live/links.firma.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/links.firma.de/privkey.pem;

    client_max_body_size 4m;     # logo upload

    location / {
        proxy_pass http://127.0.0.1:7280;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host  $host;
        proxy_read_timeout 120s;
    }
}

server {
    listen 80;
    server_name links.firma.de;
    return 301 https://$host$request_uri;
}
```

## Caddy

Caddy obtains the certificate itself. Next to `docker-compose.yml` create a
file called `Caddyfile`:

```
links.firma.de {
    reverse_proxy app:80
}
```

and add this to `docker-compose.yml` under `services:`:

```yaml
  caddy:
    image: caddy:2
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile:ro
      - caddy-data:/data
    restart: unless-stopped
```

Also add `caddy-data:` under `volumes:`. Requirement: the name points to the
server via DNS and ports 80 and 443 are reachable.


## Checking it

1. Open the page at `https://links.firma.de`.
2. **Right-click → View page source** and search for `app.css`. The link must
   start with `https://links.firma.de/`.
3. If it shows `http://localhost:7280` or `http://…`: check `APP_URL`,
   restart the container and press **Ctrl+F5** in the browser.

## Common mistakes

| Mistake | Cause and solution |
|---|---|
| Page without styling | `APP_URL` does not match the address in the browser (rule 1). |
| "Too many redirects" | Two places force HTTPS or redirect back and forth, often an upstream proxy that forwards via http internally, with Nginx Proxy Manager behind it set to "Force SSL". Force HTTPS in one place only, e.g. switch off "Force SSL" in NPM. |
| Links point to `http://`, the browser reports "mixed content" | `X-Forwarded-Proto` is missing (rule 2) or the proxy is not in `TRUSTED_PROXIES` (rule 3). The simplest fix: set `APP_URL` with `https://`. |
| The log always shows the same client IP | The proxy is not in `TRUSTED_PROXIES`; the per-address login brake then hits everyone together. Add the proxy's address. |
| HTTP 413 when uploading | Increase `client_max_body_size` in the proxy (at least 4 MB). |
| Running in a sub-path (`https://example.com/linkhub/`) | not supported – please use a separate subdomain. |
