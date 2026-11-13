#!/usr/bin/env python3
"""Verifica destinos locales y anclas Markdown. Requiere markdown-it-py 3+.

Desde la raíz: python3 docs/verificar-enlaces.py
Lee archivos versionados y nuevos no ignorados; excluye docs/superpowers.
No consulta enlaces externos ni valida fragmentos internos de PDF/SVG.
"""

import subprocess
import sys
import unicodedata
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urlsplit

from markdown_it import MarkdownIt


class HTMLLinks(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []
        self.ids = set()

    def handle_starttag(self, tag, attrs):
        for key, value in attrs:
            if value and key in {"href", "src"}:
                self.links.append(value)
            if value and (key == "id" or (tag == "a" and key == "name")):
                self.ids.add(value)


def slug(text):
    return "".join(
        c for c in text.lower()
        if c in "-_ " or unicodedata.category(c)[0] not in "PSZC"
    ).replace(" ", "-")


def inspect_markdown(path):
    tokens = MarkdownIt().parse(path.read_text(encoding="utf-8"))
    links, anchors, counts = [], set(), {}
    for index, token in enumerate(tokens):
        if token.type == "heading_open":
            children = tokens[index + 1].children or []
            title = "".join(t.content for t in children if t.type in {"text", "code_inline", "image"})
            base = slug(title)
            suffix = counts.get(base, 0)
            anchor = base if not suffix else f"{base}-{suffix}"
            while anchor in anchors:
                suffix += 1
                anchor = f"{base}-{suffix}"
            counts[base] = suffix + 1
            anchors.add(anchor)
        for child in [token, *(token.children or [])]:
            if child.type in {"link_open", "image"}:
                links.append(child.attrGet("href") or child.attrGet("src"))
            if child.type in {"html_block", "html_inline"}:
                parser = HTMLLinks()
                parser.feed(child.content)
                links.extend(parser.links)
                anchors.update(parser.ids)
    return links, anchors


def main():
    root = Path(__file__).resolve().parent.parent
    names = subprocess.check_output(
        ["git", "ls-files", "--cached", "--others", "--exclude-standard", "-z", "--", "*.md"],
        cwd=root,
    ).decode().split("\0")
    files = sorted({root / n for n in names if n and not n.startswith("docs/superpowers/")})
    documents = {p: inspect_markdown(p) for p in files if p.is_file()}
    errors, checked = [], 0
    for source, (links, _) in list(documents.items()):
        for link in links:
            url = urlsplit(link)
            if url.scheme or url.netloc:
                continue
            checked += 1
            relative = unquote(url.path)
            base = root if relative.startswith("/") else source.parent
            target = (base / relative.lstrip("/")).resolve() if relative else source
            if not target.exists():
                errors.append(f"{source.relative_to(root)}: {link} (destino inexistente)")
            elif url.fragment and target.suffix.lower() == ".md":
                if target not in documents:
                    documents[target] = inspect_markdown(target)
                if unquote(url.fragment) not in documents[target][1]:
                    errors.append(f"{source.relative_to(root)}: {link} (ancla inexistente)")
    for error in errors:
        print(error)
    print(f"{len(files)} archivos Markdown; {checked} enlaces locales; {len(errors)} errores. Excluido: docs/superpowers/.")
    return bool(errors)


if __name__ == "__main__":
    sys.exit(main())
