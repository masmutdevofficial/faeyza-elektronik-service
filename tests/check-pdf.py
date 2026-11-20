"""QA PDF yang dihasilkan browser print engine; tidak diperlukan produksi."""
import json
import re
from pathlib import Path
from pypdf import PdfReader

base = Path(__file__).parent / 'artifacts'
browser_fixture = json.loads((base / 'print-fixtures.json').read_text(encoding='utf-8'))
expected_warranty = '/'.join(reversed(browser_fixture.get('warrantyDate', '2026-12-17').split('-')))
results = []
for name in ('invoice-initial', 'invoice-final', 'invoice-long', 'invoice-pending'):
    doc = PdfReader(base / f'{name}.pdf')
    text = '\n'.join(p.extract_text() or '' for p in doc.pages)
    checks = {
        f'10 {name} ukuran A4': all(abs(float(p.mediabox.width) - 595.28) < 2 and abs(float(p.mediabox.height) - 841.89) < 2 for p in doc.pages),
        f'10 {name} navigasi dan tombol disembunyikan': all(label not in text for label in ('Cetak invoice', 'Hapus invoice', 'Detail servis', 'Workspace', 'Cetak lewat menu browser')),
        f'10 {name} konten identitas dan footer utuh': 'Faeyza Elektronik Service' in text and 'Dokumen ini diterbitkan' in text,
        f'15 {name} website bengkel tercetak': 'www.faeyzateknik.com' in text,
        f'10 {name} logo asli tertanam': any(len(page.images) > 0 for page in doc.pages),
        f'10 {name} tabel sesuai referensi': all(label in text for label in ('NOTA SERVICE','Data Konsumen','Data Perangkat','Kelengkapan','Biaya')),
        f'10 {name} syarat dan tanda tangan tersedia': all(label in text for label in ('Syarat dan ketentuan','Pihak bengkel','Pengambilan barang')),
    }
    if name == 'invoice-initial':
        checks['14 invoice belum selesai tanpa gambar tanda tangan'] = sum(len(page.images) for page in doc.pages) == 1
        checks['10 invoice awal biaya belum ditetapkan'] = 'Biaya belum ditetapkan' in text
        checks['10 invoice standar satu halaman'] = len(doc.pages) == 1
    checks['16 nama pihak bengkel Asmat tanpa sapaan'] = 'Asmat' in text and 'Bpk.' not in text
    if name == 'invoice-pending':
        checks['22 tanggal selesai dan garansi lama tidak tercetak untuk status menunggu'] = 'Menunggu Sparepart' in text and '05/04/2032' not in text and '05/04/2033' not in text
        checks['22 invoice menunggu tidak mencetak gambar tanda tangan'] = sum(len(page.images) for page in doc.pages) == 1
    if name == 'invoice-final':
        checks['14 invoice selesai mencetak gambar tanda tangan'] = sum(len(page.images) for page in doc.pages) >= 2
        checks['10 invoice final biaya Rupiah dan garansi'] = 'Rp150.000' in text and expected_warranty in text
        checks['10 invoice final satu halaman'] = len(doc.pages) == 1
    if name == 'invoice-long':
        checks['10 teks panjang seluruh sentinel tercetak'] = all(re.search(r'AKHIR-\s*' + x, text) for x in ('ADDRESS', 'COMPLAINT', 'NOTES', 'TECHNICIAN_NOTES'))
        checks['10 teks panjang mengalir ke banyak halaman'] = len(doc.pages) > 1
    for label, ok in checks.items():
        results.append({'name': label, 'status': 'PASS' if ok else 'FAIL', 'pages': len(doc.pages)})
        print(('PASS ' if ok else 'FAIL ') + label)
(base / 'print-results.json').write_text(json.dumps({'results': results}, indent=2), encoding='utf-8')
raise SystemExit(any(x['status'] == 'FAIL' for x in results))
