#!/usr/bin/env python3
"""Extract an application artifact without paths, links or unexpected top-level files."""
import pathlib
import sys
import tarfile

archive, destination = sys.argv[1:]
allowed = {"app", "artisan", "bootstrap", "config", "contracts", "database", "public",
           "resources", "routes", "vendor", "composer.json", "composer.lock"}
with tarfile.open(archive, "r:gz") as bundle:
    entries = bundle.getmembers()
    if len(entries) > 100000 or sum(entry.size for entry in entries) > 1073741824:
        raise ValueError("Artifact exceeds extraction limits")
    for entry in entries:
        path = pathlib.PurePosixPath(entry.name)
        if (path.is_absolute() or ".." in path.parts or not path.parts
                or path.parts[0] not in allowed or not (entry.isfile() or entry.isdir())
                or any(part.startswith(".env") for part in path.parts)
                or entry.name == "public/hot"):
            raise ValueError("Unsafe artifact member")
    bundle.extractall(destination, members=entries, filter="data")
