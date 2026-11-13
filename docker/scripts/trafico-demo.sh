#!/usr/bin/env bash
set -euo pipefail

if [[ $# -gt 1 || ! ${1:-120} =~ ^[1-9][0-9]{0,5}$ ]] || (( ${1:-120} > 86400 )); then
    echo 'Uso: trafico-demo.sh [segundos: 1..86400; predeterminado: 120]' >&2
    exit 2
fi

duracion=${1:-120}
rutas=(/ /perfumes /perfumes/coromandel /up)
declare -A por_nodo=([nginx-php-fpm]=0 [apache-mod-php]=0 [sin-nodo]=0)
declare -A por_codigo=()
total=0
errores=0
curl_pid=''

finalizar() {
    local resultado=$? nodo codigo
    if [[ -n "$curl_pid" ]]; then
        kill "$curl_pid" 2>/dev/null || true
        wait "$curl_pid" 2>/dev/null || true
    fi
    printf '\nResumen: %s respuestas; %s respuestas inesperadas (HTTP distinto de 200 o sin nodo).\n' "$total" "$errores"
    for nodo in nginx-php-fpm apache-mod-php sin-nodo; do
        printf '  %s: %s\n' "$nodo" "${por_nodo[$nodo]}"
    done
    for codigo in "${!por_codigo[@]}"; do
        printf '  HTTP %s: %s\n' "$codigo" "${por_codigo[$codigo]}"
    done
    return "$resultado"
}
trap finalizar EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

printf 'Tráfico contra http://localhost durante %s s, hasta 2 solicitudes/s. Ctrl+C para terminar.\n' "$duracion"
inicio=$SECONDS
exec {respuestas}< <(
    exec curl --silent --connect-timeout 2 --max-time 5 \
        --rate 2/s --write-out '%{stderr}%header{x-servido-por}|%{http_code}\n' \
        --config <(
            for ((i=0; i<duracion*2; i++)); do
                printf 'url = "http://localhost%s"\noutput = "/dev/null"\n' "${rutas[i % ${#rutas[@]}]}"
            done
        ) 2>&1
)
curl_pid=$!
while IFS='|' read -r nodo codigo <&"$respuestas"; do
    case "$nodo" in
        nginx-php-fpm|apache-mod-php) ;;
        *) nodo=sin-nodo ;;
    esac
    total=$((total + 1))
    por_nodo[$nodo]=$((por_nodo[$nodo] + 1))
    por_codigo[$codigo]=$((${por_codigo[$codigo]:-0} + 1))
    if [[ "$codigo" != 200 || "$nodo" == sin-nodo ]]; then
        errores=$((errores + 1))
    fi
    printf '#%s %s HTTP %s | A=%s B=%s sin-nodo=%s\n' "$total" "$nodo" "$codigo" \
        "${por_nodo[nginx-php-fpm]}" "${por_nodo[apache-mod-php]}" "${por_nodo[sin-nodo]}"
    if (( SECONDS - inicio >= duracion )); then
        break
    fi
done
exec {respuestas}<&-
if (( total == 0 || errores > 0 )); then
    exit 1
fi
