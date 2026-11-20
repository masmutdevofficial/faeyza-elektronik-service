"""Render the repository installation guide to a client PDF; QA uses Poppler."""
from html import escape
from pathlib import Path
import re
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Preformatted, PageBreak, KeepTogether

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'output' / 'delivery'
OUT.mkdir(parents=True, exist_ok=True)
pdfmetrics.registerFont(TTFont('Guide', 'C:/Windows/Fonts/arial.ttf'))
pdfmetrics.registerFont(TTFont('GuideBold', 'C:/Windows/Fonts/arialbd.ttf'))
pdfmetrics.registerFontFamily('Guide', normal='Guide', bold='GuideBold', italic='Guide', boldItalic='GuideBold')
styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='BodyGuide', fontName='Guide', fontSize=9.2, leading=13, spaceAfter=7))
styles.add(ParagraphStyle(name='TitleGuide', fontName='GuideBold', fontSize=19, leading=24, spaceAfter=14, textColor=colors.HexColor('#12375c')))
styles.add(ParagraphStyle(name='SectionGuide', fontName='GuideBold', fontSize=15, leading=20, spaceAfter=12, textColor=colors.HexColor('#0756b5')))
styles.add(ParagraphStyle(name='CodeGuide', fontName='Courier', fontSize=7.4, leading=10.3, leftIndent=9, rightIndent=9, spaceBefore=3, spaceAfter=10, backColor=colors.HexColor('#f0f4f8'), borderPadding=8))

def inline(value):
    value = escape(value)
    value = re.sub(r'`([^`]+)`', r'<font name="Courier" size="8">\1</font>', value)
    value = re.sub(r'\*\*([^*]+)\*\*', r'<b>\1</b>', value)
    return value

story=[]; paragraph=[]; code=[]; in_code=False
def flush():
    if paragraph:
        story.append(Paragraph(inline(' '.join(paragraph)), styles['BodyGuide']))
        paragraph.clear()

for line in (ROOT/'docs/PANDUAN-INSTALASI.md').read_text(encoding='utf-8').splitlines():
    if line.startswith('```'):
        flush()
        if in_code:
            # Keep commands intact; fail QA rather than silently wrapping executable text.
            if any(pdfmetrics.stringWidth(value, 'Courier', 7.4)>474 for value in code):
                raise ValueError('Code line too long for PDF')
            story.append(Preformatted('\n'.join(code), styles['CodeGuide']));code.clear()
        in_code=not in_code;continue
    if in_code: code.append(line);continue
    if line.startswith('# '): flush();story.append(Paragraph(inline(line[2:]),styles['TitleGuide']))
    elif line.startswith('## '):
        flush()
        if not line.startswith('## 1 '): story.append(PageBreak())
        story.append(Paragraph(inline(line[3:]),styles['SectionGuide']))
    elif not line.strip(): flush()
    elif re.match(r'^\d+\. ',line) or line.startswith('- '):
        flush();story.append(Paragraph(inline(line),styles['BodyGuide']))
    else: paragraph.append(line)
flush()

def decorate(canvas,doc):
    width,height=A4
    canvas.saveState();canvas.setStrokeColor(colors.HexColor('#d5e1ed'));canvas.line(42,height-39,width-42,height-39)
    canvas.setFont('GuideBold',8);canvas.setFillColor(colors.HexColor('#0756b5'));canvas.drawString(42,height-29,'FAEYZA ELEKTRONIK SERVICE')
    canvas.setFont('Guide',8);canvas.setFillColor(colors.HexColor('#53677b'));canvas.drawRightString(width-42,height-29,'Panduan instalasi')
    canvas.setFont('Guide',8);canvas.drawString(42,27,'PHP 8.2+  |  MySQL / MariaDB  |  18 November 2026');canvas.drawRightString(width-42,27,str(doc.page));canvas.restoreState()

doc=SimpleDocTemplate(str(OUT/'PANDUAN-INSTALASI.pdf'),pagesize=A4,rightMargin=45,leftMargin=45,topMargin=53,bottomMargin=47,title='Panduan instalasi Faeyza Elektronik Service',author='Faeyza Elektronik Service')
doc.build(story,onFirstPage=decorate,onLaterPages=decorate)
print(OUT/'PANDUAN-INSTALASI.pdf')
