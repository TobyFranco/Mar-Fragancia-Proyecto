#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

restaurar=false
restaurar_nodo() {
    local resultado=$?
    if [[ "$restaurar" == true ]]; then
        echo "Restaurando web-nginx tras interrumpir la demo..." >&2
        docker compose start web-nginx || echo "No se pudo restaurar web-nginx; ejecutar docker compose start web-nginx." >&2
    fi
    return "$resultado"
}
trap restaurar_nodo EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

consultar_nodo() {
    local cabeceras nodo codigo
    cabeceras=$(curl --fail --silent --show-error --connect-timeout 2 --max-time 10 \
        --dump-header - --output /dev/null http://localhost/up) || return 1
    codigo=$(printf '%s\n' "$cabeceras" | awk '/^HTTP\// {codigo=$2} END {gsub("\r", "", codigo); print codigo}')
    if [[ "$codigo" != 200 ]]; then
        echo "Se esperaba HTTP 200; se recibió HTTP $codigo." >&2
        return 1
    fi
    nodo=$(printf '%s\n' "$cabeceras" | awk 'tolower($1) == "x-servido-por:" {gsub("\r", "", $2); print $2}')
    case "$nodo" in
        nginx-php-fpm|apache-mod-php) printf '%s\n' "$nodo" ;;
        *) echo "Respuesta sin cabecera X-Servido-Por válida." >&2; return 1 ;;
    esac
}

mostrar_balanceo() {
    local visto_a=false visto_b=false nodo
    for ((i=0; i<6; i++)); do
        nodo=$(consultar_nodo) || return 1
        echo "HTTP 200 - X-Servido-Por: $nodo"
        if [[ "$nodo" == nginx-php-fpm ]]; then visto_a=true; else visto_b=true; fi
    done
    if [[ "$visto_a" != true || "$visto_b" != true ]]; then
        echo "No respondieron ambos nodos; revisar sus health checks en Stats." >&2
        return 1
    fi
}

echo "1. Balanceo con los dos nodos disponibles:"
mostrar_balanceo

echo "2. Deteniendo web-nginx y esperando su baja en los health checks..."
restaurar=true
docker compose stop web-nginx
sleep 8
for ((i=0; i<6; i++)); do
    nodo=$(consultar_nodo)
    if [[ "$nodo" != apache-mod-php ]]; then
        echo "Se esperaba solo apache-mod-php con web-nginx detenido." >&2
        exit 1
    fi
    echo "HTTP 200 - X-Servido-Por: $nodo"
done

echo "3. Iniciando web-nginx y esperando su recuperación..."
docker compose start web-nginx
restaurar=false
recuperado=false
for ((i=0; i<20; i++)); do
    nodo=$(consultar_nodo)
    echo "HTTP 200 - X-Servido-Por: $nodo"
    if [[ "$nodo" == nginx-php-fpm ]]; then
        recuperado=true
        break
    fi
    sleep 1
done
if [[ "$recuperado" != true ]]; then
    echo "nginx-php-fpm no volvió a estar disponible dentro del plazo." >&2
    exit 1
fi
mostrar_balanceo
echo "Demo completada: ambos nodos vuelven a responder."
