FROM nginx:latest

COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ./nginx/certs/ /tmp/certs/

RUN mkdir -p /etc/nginx/certs \
    && if [ -f /tmp/certs/host.docker.internal.pem ] && [ -f /tmp/certs/host.docker.internal-key.pem ]; then \
         cp /tmp/certs/host.docker.internal.pem /tmp/certs/host.docker.internal-key.pem /etc/nginx/certs/; \
       else \
         apt-get update \
         && apt-get install -y --no-install-recommends openssl \
         && openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
              -keyout /etc/nginx/certs/host.docker.internal-key.pem \
              -out /etc/nginx/certs/host.docker.internal.pem \
              -subj "/CN=localhost" \
         && rm -rf /var/lib/apt/lists/*; \
       fi
