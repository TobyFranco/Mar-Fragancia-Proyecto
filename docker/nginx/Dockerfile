FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY src/package*.json ./
RUN npm ci --no-audit --no-fund
COPY src/ ./
RUN npm run build

FROM nginx:alpine
# Nginx lleva su propia copia de public para servir estáticos sin bind mounts.
COPY src/public/ /var/www/public/
COPY --from=assets /build/public/build /var/www/public/build
COPY docker/nginx/templates/ /etc/nginx/templates/
RUN ln -s /var/www/storage/app/public /var/www/public/storage
