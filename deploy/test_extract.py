"""Exercise the untrusted archive boundary used by the production receiver."""
import io
from pathlib import Path
import subprocess
import sys
import tarfile
import tempfile
import unittest


class ExtractionTest(unittest.TestCase):
    def extract(self, name, kind=tarfile.REGTYPE, link=""):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            archive = root / "release.tar.gz"
            member = tarfile.TarInfo(name)
            member.type = kind
            member.linkname = link
            data = b"test"
            member.size = len(data) if kind == tarfile.REGTYPE else 0
            with tarfile.open(archive, "w:gz") as output:
                output.addfile(member, io.BytesIO(data) if member.size else None)
            destination = root / "output"
            destination.mkdir()
            result = subprocess.run(
                [sys.executable, str(Path(__file__).with_name("extract.py")), str(archive), str(destination)],
                capture_output=True,
            )
            return result.returncode, list(destination.rglob("*"))

    def test_application_file_is_accepted(self):
        code, paths = self.extract("public/index.php")
        self.assertEqual(code, 0)
        self.assertTrue(any(path.name == "index.php" for path in paths))

    def test_paths_links_devices_and_secrets_are_rejected_before_extraction(self):
        cases = [
            ("../../escape", tarfile.REGTYPE, ""),
            ("/public/index.php", tarfile.REGTYPE, ""),
            ("public/../../escape", tarfile.REGTYPE, ""),
            ("public/link", tarfile.SYMTYPE, "/etc/passwd"),
            ("public/link", tarfile.LNKTYPE, "app/file.php"),
            ("public/device", tarfile.CHRTYPE, ""),
            (".local/private", tarfile.REGTYPE, ""),
            ("public/.env.production", tarfile.REGTYPE, ""),
            ("public/hot", tarfile.REGTYPE, ""),
        ]
        for name, kind, link in cases:
            with self.subTest(name=name, kind=kind):
                code, paths = self.extract(name, kind, link)
                self.assertNotEqual(code, 0)
                self.assertEqual(paths, [])


if __name__ == "__main__":
    unittest.main()
