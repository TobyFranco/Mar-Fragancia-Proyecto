#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

restaurar=false
restaurar_nodo() {
    local resultado=$?
    if [[ "$restaurar" == true ]]; then
        echo "Restaurando web-a tras interrumpir la demo..." >&2
        docker compose start web-a || echo "No se pudo restaurar web-a; ejecutar docker compose start web-a." >&2
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
        arq-a|arq-b) printf '%s\n' "$nodo" ;;
        *) echo "Respuesta sin cabecera X-Servido-Por válida." >&2; return 1 ;;
    esac
}

mostrar_balanceo() {
    local visto_a=false visto_b=false nodo
    for ((i=0; i<6; i++)); do
        nodo=$(consultar_nodo) || return 1
        echo "HTTP 200 - X-Servido-Por: $nodo"
        if [[ "$nodo" == arq-a ]]; then visto_a=true; else visto_b=true; fi
    done
    if [[ "$visto_a" != true || "$visto_b" != true ]]; then
        echo "No respondieron ambos nodos; revisar sus health checks en Stats." >&2
        return 1
    fi
}

echo "1. Balanceo con los dos nodos disponibles:"
mostrar_balanceo

echo "2. Deteniendo web-a y esperando su baja en los health checks..."
restaurar=true
docker compose stop web-a
sleep 8
for ((i=0; i<6; i++)); do
    nodo=$(consultar_nodo)
    if [[ "$nodo" != arq-b ]]; then
        echo "Se esperaba solo arq-b con web-a detenido." >&2
        exit 1
    fi
    echo "HTTP 200 - X-Servido-Por: $nodo"
done

echo "3. Iniciando web-a y esperando su recuperación..."
docker compose start web-a
restaurar=false
recuperado=false
for ((i=0; i<20; i++)); do
    nodo=$(consultar_nodo)
    echo "HTTP 200 - X-Servido-Por: $nodo"
    if [[ "$nodo" == arq-a ]]; then
        recuperado=true
        break
    fi
    sleep 1
done
if [[ "$recuperado" != true ]]; then
    echo "arq-a no volvió a estar disponible dentro del plazo." >&2
    exit 1
fi
mostrar_balanceo
echo "Demo completada: ambos nodos vuelven a responder."
