"""Place unmodified print artwork on A4; vector QR and external cut marks."""
from pathlib import Path
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.utils import ImageReader
from reportlab.graphics.shapes import Drawing
from reportlab.graphics.barcode.qr import QrCodeWidget
from reportlab.graphics import renderPDF
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'output' / 'pdf'
URLS = {'motoristas': 'https://zvtech.com.br/cadastro?perfil=driver',
        'oficinas': 'https://zvtech.com.br/cadastro?perfil=workshop'}

def marks(c, x, y, w, h):
    c.setStrokeColorRGB(.55,.55,.55)
    c.setLineWidth(.25)
    gap, length = .8*mm, 1.8*mm
    for cx in (x,x+w):
        for cy in (y,y+h):
            direction_x = -1 if cx == x else 1
            direction_y = -1 if cy == y else 1
            c.line(cx+direction_x*gap,cy,cx+direction_x*(gap+length),cy)
            c.line(cx,cy+direction_y*gap,cx,cy+direction_y*(gap+length))

def card(c, kind, x, y):
    w, footer = 94*mm, 22*mm
    image = ImageReader(str(ROOT/'assets'/'impressao'/f'flyer-{kind}.png'))
    iw, ih = image.getSize()
    h = w * ih / iw
    assert h + footer <= 140*mm
    # Scaling for imposition only: no raster edits, clipping or stretching.
    c.drawImage(image,x,y+footer,width=w,height=h)
    qr = QrCodeWidget(URLS[kind],barLevel='M',barBorder=4)
    x0,y0,x1,y1 = qr.getBounds()
    size = 20*mm
    drawing = Drawing(size,size,transform=[size/(x1-x0),0,0,size/(y1-y0),0,0])
    drawing.add(qr)
    renderPDF.draw(drawing,c,x+w-size-1*mm,y+1*mm)
    c.setFillColorRGB(.02,.12,.25)
    c.setFont('Helvetica-Bold',8)
    c.drawString(x+3*mm,y+14*mm,'Aponte a câmera e comece')
    c.setFont('Helvetica',7)
    label = 'Cadastro de motorista' if kind == 'motoristas' else 'Cadastro de oficina'
    c.drawString(x+3*mm,y+9.5*mm,label)
    c.drawString(x+3*mm,y+5*mm,'Ou acesse zvtech.com.br')
    c.linkURL(URLS[kind],(x+w-size-1*mm,y+1*mm,x+w-1*mm,y+21*mm),relative=0)
    marks(c,x,y,w,h+footer)

def build(name,kinds):
    path=OUT/name
    c=canvas.Canvas(str(path),pagesize=A4,pageCompression=1)
    c.setTitle('ZVTech Checklist - 4 flyers por folha A4')
    c.setAuthor('ZVTech / RQCODE')
    positions=[(9,150.5),(107,150.5),(9,7),(107,7)]
    for kind,(x,y) in zip(kinds,positions):card(c,kind,x*mm,y*mm)
    c.showPage();c.save()
    pdf=PdfReader(path)
    assert len(pdf.pages)==1
    page=pdf.pages[0]
    assert abs(float(page.mediabox.width)-A4[0])<.1
    assert abs(float(page.mediabox.height)-A4[1])<.1
    annotations=page['/Annots']
    assert len(annotations)==4
    actual=[str(a.get_object()['/A']['/URI']) for a in annotations]
    assert actual==[URLS[k] for k in kinds]
    assert page.extract_text().count('Aponte a câmera e comece')==4
    print(f'PASS {name}: A4, 1 página, 4 peças, links corretos')

if __name__=='__main__':
    OUT.mkdir(parents=True,exist_ok=True)
    build('motoristas-A4-4-copias.pdf',['motoristas']*4)
    build('oficinas-A4-4-copias.pdf',['oficinas']*4)
    build('misto-A4-2-motoristas-2-oficinas.pdf',['motoristas','oficinas','motoristas','oficinas'])
