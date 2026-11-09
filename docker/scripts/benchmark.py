"""ApacheBench en la red Compose; sin cambios a datos comerciales."""
import csv
import json
import platform
import tempfile
import os
from pathlib import Path
import re
import signal
import subprocess
import time

ROOT = Path(__file__).resolve().parents[2]
OUTPUT = Path(os.environ.get('BENCHMARK_OUTPUT', ROOT / 'docs/benchmark')).resolve()
RAW = OUTPUT / 'resultados'
N = int(os.environ.get('BENCHMARK_REQUESTS', '2000'))
HOLD = float(os.environ.get('BENCHMARK_HOLD_SECONDS', '3'))
FIELDS = ['arquitectura', 'endpoint', 'concurrencia', 'rps', 'latencia_media_ms', 'p50_ms', 'p95_ms', 'p99_ms', 'errores', 'requests']


def docker(*args, **kwargs):
    return subprocess.run(['docker', 'compose', *args], cwd=ROOT, check=True, text=True, **kwargs)


def artisan(command, production=False):
    args = ['exec', '-T']
    if production:
        args += ['-e', 'APP_ENV=production', '-e', 'APP_DEBUG=false']
    docker(*args, 'app-fpm', 'php', 'artisan', command)


def parse(text):
    def value(pattern, optional=False):
        match = re.search(pattern, text, re.M)
        if not match and optional:
            return 0
        if not match:
            raise ValueError('Salida ab incompleta: ' + pattern)
        return float(match[1])
    return [value(r'^Requests per second:\s+([\d.]+)'),
            value(r'^Total:\s+\S+\s+([\d.]+)'),
            *[value(r'^\s*' + str(p) + r'%\s+([\d.]+)') for p in (50, 95, 99)],
            int(value(r'^Failed requests:\s+(\d+)') + value(r'^Non-2xx responses:\s+(\d+)', True)),
            int(value(r'^Complete requests:\s+(\d+)'))]


def measure(writer, arch, host, endpoint, concurrency, name, warm=False, failover=False):
    args = ['docker', 'compose', 'run', '--rm', '--no-deps', 'benchmark', '-l', '-r', '-s', '60', '-n', str(30 if warm else N), '-c', str(concurrency)]
    if failover:
        args += ['-t', '15', '-n', '1000000']
    args += ['http://' + host + endpoint]
    path = RAW / (name + '.txt')
    print('Midiendo', name, flush=True)
    before = stats('stats-pre-failover.csv') if failover else 0
    with path.open('w') as log:
        process = subprocess.Popen(args, cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, text=True)
        try:
            if failover:
                deadline = time.monotonic() + 30
                while True:
                    current = stats('stats-carga-iniciada.csv')
                    if process.poll() is not None:
                        raise RuntimeError('La carga terminó antes de provocar la caída')
                    if current > before:
                        break
                    if time.monotonic() >= deadline:
                        raise RuntimeError('No se observó tráfico activo en Stats')
                    time.sleep(0.1)
                docker('stop', 'web-nginx')
                time.sleep(HOLD)
                docker('start', 'web-nginx')
            code = process.wait(timeout=300)
        finally:
            if process.poll() is None:
                process.terminate()
                process.wait(timeout=15)
        if code:
            raise RuntimeError(f'ab terminó con código {code}: {path}')
    metrics = parse(path.read_text())
    if warm and metrics[-2]:
        raise RuntimeError('Warm-up con errores: ' + name)
    if not warm:
        writer.writerow([arch, endpoint, concurrency, *metrics])


def stats(name):
    password = docker('exec', '-T', 'haproxy', 'printenv', 'HAPROXY_STATS_PASSWORD', capture_output=True).stdout.strip()
    result = docker('exec', '-T', 'app-fpm', 'curl', '--fail', '--silent', '--show-error', '-u', 'admin:' + password, 'http://haproxy:8404/;csv', capture_output=True)
    (RAW / name).write_text(result.stdout)
    data = csv.DictReader(result.stdout.lstrip('# ').splitlines())
    return sum(int(row['stot'] or 0) for row in data if row['svname'] in ('nginx-php-fpm', 'apache-mod-php'))


def verify_opcache():
    with tempfile.NamedTemporaryFile(dir=ROOT / 'src/public', prefix='benchmark-opcache-', suffix='.php', delete=False) as probe:
        path = Path(probe.name)
        probe.write(b"<?php echo (opcache_get_status(false)['opcache_enabled'] ?? false) ? 'OPCACHE_OK' : 'OPCACHE_OFF';")
    try:
        path.chmod(0o644)
        for host in ('web-nginx', 'apache-php'):
            result = docker('exec', '-T', 'app-fpm', 'curl', '--fail', '--silent', '--show-error', 'http://' + host + '/' + path.name, capture_output=True)
            if result.stdout.strip() != 'OPCACHE_OK':
                raise RuntimeError('OPcache no activo en el SAPI web de ' + host)
    finally:
        path.unlink(missing_ok=True)


def main():
    if N < 100:
        raise ValueError('BENCHMARK_REQUESTS debe ser al menos 100')
    RAW.mkdir(parents=True, exist_ok=True)
    css = sorted((ROOT / 'src/public/build/assets').glob('app-*.css'))[0]
    endpoints = ['/up', '/perfumes', '/perfumes/coromandel', '/build/assets/' + css.name]
    verify_opcache()
    metadata = {'fecha_utc': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime()), 'host': platform.platform(), 'requests': N, 'herramienta': 'ApacheBench httpd:2.4.65-alpine', 'opcache_web': 'activo en ambos', 'docker': docker('images', '--format', 'json', capture_output=True).stdout}
    (OUTPUT / 'entorno.json').write_text(json.dumps(metadata, indent=2))
    try:
        artisan('config:cache', True)
        artisan('route:cache')
        artisan('view:cache')
        with (OUTPUT / 'resultados.csv').open('w', newline='') as file:
            writer = csv.writer(file)
            writer.writerow(FIELDS)
            for arch, host in [('nginx-php-fpm', 'web-nginx'), ('apache-mod-php', 'apache-php')]:
                for index, endpoint in enumerate(endpoints):
                    measure(writer, arch, host, endpoint, 1, f'{arch}-{index}-warm', warm=True)
                    for concurrency in (10, 50, 100):
                        measure(writer, arch, host, endpoint, concurrency, f'{arch}-{index}-c{concurrency}')
                        file.flush()
            stats('stats-antes.csv')
            measure(writer, 'haproxy', 'haproxy', '/perfumes', 50, 'haproxy-normal')
            stats('stats-normal.csv')
            measure(writer, 'haproxy-failover', 'haproxy', '/perfumes', 50, 'haproxy-failover', failover=True)
            stats('stats-failover.csv')
    finally:
        # No modificar .env ni regenerar la clave. Restaurar incluso ante error.
        failures = []
        for operation in [lambda: docker('start', 'web-nginx'), *[lambda c=c: artisan(c) for c in ('config:clear', 'route:clear', 'view:clear')]]:
            try:
                operation()
            except Exception as error:
                failures.append(str(error))
        if failures:
            raise RuntimeError('Falló restauración: ' + '; '.join(failures))


if __name__ == '__main__':
    signal.signal(signal.SIGTERM, lambda *_: (_ for _ in ()).throw(KeyboardInterrupt()))
    main()
