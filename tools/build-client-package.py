"""Build a client ZIP from an explicit allowlist. No runtime configuration/data."""
import hashlib
import json
import argparse
from datetime import date
from pathlib import Path
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'output' / 'delivery'
PREFIX = 'faeyza-elektronik-service/'

def build(release_date='2026-11-20'):
    release_day=date.fromisoformat(release_date)
    OUT.mkdir(parents=True, exist_ok=True)
    files = {}
    for directory in ['app', 'config', 'database', 'public', 'views', 'deploy']:
        for path in sorted((ROOT / directory).rglob('*')):
            if path.is_file():
                assert not path.is_symlink(), path
                files[path.relative_to(ROOT).as_posix()] = path.read_bytes()
    for name in ['setup.php', 'migrate.php', 'doctor.php', 'create-admin.php', 'admin-sql.php', 'start.ps1']:
        files['bin/' + name] = (ROOT / 'bin' / name).read_bytes()
    for name in ['.env.example', '.htaccess']:
        files[name] = (ROOT / name).read_bytes()
    for name in ['PANDUAN-INSTALASI.md', 'HASIL-VERIFIKASI.md', 'PANDUAN-SUBDOMAIN.md', 'PERBAIKAN-RINGKASAN-STOK.md', 'TIPE-SPAREPART.md', 'PENGHAPUSAN-DATA.md']:
        path = ROOT / 'docs' / name
        if path.is_file(): files['docs/' + name] = path.read_bytes()
    files['PANDUAN-INSTALASI.pdf'] = (OUT / 'PANDUAN-INSTALASI.pdf').read_bytes()
    for name in ['logs', 'sessions']:
        files[f'storage/{name}/.gitkeep'] = b''
    files['MULAI-DISINI.txt'] = (
        'FAEYZA ELEKTRONIK SERVICE\n\n'
        '1. Buka PANDUAN-INSTALASI.pdf.\n'
        '2. Uji XAMPP: salin folder ini menjadi C:\\xampp\\htdocs\\bengkel.\n'
        '3. Buat database baru dan konfigurasi .env dari .env.example.\n'
        '4. Jalankan setup dan buat admin baru sesuai panduan.\n\n'
        'Paket tidak berisi akun/password bawaan atau data pelanggan.\n'
        'Jangan upload seluruh folder ke public_html. Ikuti susunan pada panduan.\n'
    ).encode('utf-8')
    files['README.md'] = (
        '# Faeyza Elektronik Service\n\n'
        'Aplikasi servis elektronik PHP 8.2+ dan MySQL/MariaDB. '
        'Tidak memerlukan Node.js atau Composer.\n\n'
        'Mulai dari [panduan PDF](PANDUAN-INSTALASI.pdf) atau '
        '[panduan teks](docs/PANDUAN-INSTALASI.md). '
        'Hasil pemeriksaan paket ada di docs/HASIL-VERIFIKASI.md.\n\n'
        'Aplikasi dan skema kosong disertakan. Konfigurasi .env, akun, password, '
        'data pelanggan, log, sesi, riwayat Git, data seed dan hasil testing privat tidak disertakan. '
        'Logo, tanda tangan bengkel dan lisensi ikon MDI tetap disertakan sebagai aset aplikasi.\n\n'
        'Login pelanggan: halaman utama. Login admin: /admin/. '
        'Buat akun admin pertama lewat bin/create-admin.php atau helper offline '
        'bin/admin-sql.php sesuai panduan. Tidak ada endpoint setup web.\n\n'
        'Untuk hosting dengan public_html tetap, aplikasi lengkap tetap berada '
        'di luar public_html. Contoh wrapper tersedia di deploy/shared-hosting/index.php.example.\n'
    ).encode('utf-8')
    source = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
    manifest = {'application': 'Faeyza Elektronik Service', 'release_date': release_date,
                'source_base_commit': source,
                'note': 'Includes active customer summaries, audited stock adjustments, sparepart types (schema v4), and permanent customer/invoice deletion (scoped runtime DELETE grants). File hashes identify exact contents; source_base_commit identifies HEAD at packaging time and may precede uncommitted workspace changes. No database backup.',
                'files': {name: hashlib.sha256(data).hexdigest() for name, data in sorted(files.items())}}
    files['MANIFEST.json'] = json.dumps(manifest, indent=2, ensure_ascii=False).encode('utf-8')
    zip_path = OUT / 'faeyza-elektronik-service.zip'
    with zipfile.ZipFile(zip_path, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, data in sorted(files.items()):
            info = zipfile.ZipInfo(PREFIX + name, date_time=(release_day.year, release_day.month, release_day.day, 12, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, data)
    digest = hashlib.sha256(zip_path.read_bytes()).hexdigest()
    (OUT / 'faeyza-elektronik-service.zip.sha256').write_text(digest + '  ' + zip_path.name + '\n', encoding='ascii')
    print(json.dumps({'archive': str(zip_path), 'files': len(files), 'bytes': zip_path.stat().st_size, 'sha256': digest}))

if __name__ == '__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--release-date',default='2026-11-20')
    build(parser.parse_args().release_date)
