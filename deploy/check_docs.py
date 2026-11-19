"""Verify public documentation entry aliases against their canonical pages."""
import sys
from urllib.parse import urlsplit
from urllib.request import urlopen


def check(base):
    base = base.rstrip("/")
    origin = urlsplit(base)
    if origin.scheme not in ("http", "https") or not origin.netloc:
        raise ValueError("Expected an HTTP(S) application origin")

    def fetch(path):
        with urlopen(base + path, timeout=30) as response:
            final = urlsplit(response.url)
            if (final.scheme, final.netloc) != (origin.scheme, origin.netloc):
                raise RuntimeError(f"Unexpected redirect for {path}")
            if response.status != 200 or response.headers.get_content_type() != "text/html":
                raise RuntimeError(f"Expected an HTML page for {path}")
            return response.read()

    count = 0
    for locale in ("", "en", "zh-CN", "ja"):
        directory = "/docs" + (f"/{locale}" if locale else "")
        canonical = fetch(directory + "/index.html")
        if b"CareerAtlas" not in canonical or b"<h1" not in canonical:
            raise RuntimeError(f"Missing documentation content for {directory}")
        if locale and f'<html lang="{locale}"'.encode() not in canonical:
            raise RuntimeError(f"Incorrect document language for {directory}")
        for suffix in ("", "/", "/?source=shared-link"):
            if fetch(directory + suffix) != canonical:
                raise RuntimeError(f"Directory entry does not serve its index: {directory + suffix}")
        count += 4
    print(f"PASS: {count} public documentation entry URLs, including directory aliases")


if __name__ == "__main__":
    check(sys.argv[1])
