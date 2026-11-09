"""Ejecutar con python3 -I (o el Python del venv temporal)."""
import csv
from pathlib import Path
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

ROOT = Path(__file__).resolve().parent
with (ROOT / 'resultados.csv').open() as file:
    rows = list(csv.DictReader(file))
plt.rcParams.update({'font.family': 'DejaVu Sans', 'axes.spines.top': False, 'axes.spines.right': False, 'axes.facecolor': '#f2f6f1', 'figure.facecolor': '#ffffff'})
for endpoint, name in [('/up', 'up'), ('/perfumes', 'catalogo'), ('/perfumes/coromandel', 'ficha')]:
    for metric, label in [('rps', 'Solicitudes por segundo'), ('p95_ms', 'Latencia p95 (ms)')]:
        fig, ax = plt.subplots(figsize=(7, 4.2), layout='constrained')
        for arch, color in [('nginx-php-fpm', '#536b50'), ('apache-mod-php', '#9baf91')]:
            data = sorted((r for r in rows if r['arquitectura'] == arch and r['endpoint'] == endpoint), key=lambda r: int(r['concurrencia']))
            ax.plot([int(r['concurrencia']) for r in data], [float(r[metric]) for r in data], marker='o', color=color, label=arch)
        ax.set(xlabel='Solicitudes concurrentes', ylabel=label, title=endpoint, xticks=[10, 50, 100], ylim=(0, None))
        ax.grid(alpha=.2)
        ax.legend()
        fig.savefig(ROOT / f'{name}-{metric}.png', dpi=180)
        plt.close(fig)
