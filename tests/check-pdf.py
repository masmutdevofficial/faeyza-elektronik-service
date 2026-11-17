"""QA PDF yang dihasilkan browser print engine; tidak diperlukan produksi."""
import json
import re
from pathlib import Path
from pypdf import PdfReader

base = Path(__file__).parent / 'artifacts'
browser_fixture = json.loads((base.parent / 'local-browser-fixtures.json').read_text(encoding='utf-8'))
expected_warranty = '/'.join(reversed(browser_fixture.get('warrantyDate', '2026-12-17').split('-')))
results = []
for name in ('invoice-initial', 'invoice-final', 'invoice-long'):
    doc = PdfReader(base / f'{name}.pdf')
    text = '\n'.join(p.extract_text() or '' for p in doc.pages)
    checks = {
        f'10 {name} ukuran A4': all(abs(float(p.mediabox.width) - 595.28) < 2 and abs(float(p.mediabox.height) - 841.89) < 2 for p in doc.pages),
        f'10 {name} navigasi dan tombol disembunyikan': 'Cetak invoice' not in text and 'Detail servis' not in text and 'Workspace' not in text,
        f'10 {name} konten identitas dan footer utuh': 'Faeyza Elektronik Service' in text and 'Dokumen ini diterbitkan' in text,
        f'10 {name} logo asli tertanam': any(len(page.images) > 0 for page in doc.pages),
        f'10 {name} tabel sesuai referensi': all(label in text for label in ('NOTA SERVICE','Data Konsumen','Data Perangkat','Kelengkapan','Biaya')),
        f'10 {name} syarat dan tanda tangan tersedia': all(label in text for label in ('Syarat dan ketentuan','Pihak bengkel','Pengambilan barang')),
    }
    if name == 'invoice-initial':
        checks['10 invoice awal biaya belum ditetapkan'] = 'Biaya belum ditetapkan' in text
        checks['10 invoice standar satu halaman'] = len(doc.pages) == 1
    if name == 'invoice-final':
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
