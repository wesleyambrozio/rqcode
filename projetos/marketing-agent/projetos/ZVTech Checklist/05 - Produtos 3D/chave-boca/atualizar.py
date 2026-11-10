import bpy,bmesh,os,json,math,zipfile,xml.etree.ElementTree as E
from mathutils import Vector
R=os.path.dirname(os.path.abspath(__file__))
SOURCE=r'C:\desenvolvimento\projetos_3D\chaveTripo\original_78mm\revisao_base_lisa\texto_maior_10'
bpy.ops.wm.open_mainfile(filepath=os.path.join(SOURCE,'Chave_78mm_texto_maior.blend'))
body=bpy.data.objects['01_CORPO_PETG_METAL_BASE_PLANA'];text=bpy.data.objects['02_SITE_PETG_PRETO']
def active(o):
 bpy.ops.object.select_all(action='DESELECT');o.select_set(True);bpy.context.view_layer.objects.active=o
def validated(o):
 bm=bmesh.new();bm.from_mesh(o.data);bmesh.ops.recalc_face_normals(bm,faces=list(bm.faces));bmesh.ops.triangulate(bm,faces=list(bm.faces));bad=sum(not e.is_manifold for e in bm.edges);vol=bm.calc_volume();assert bad==0 and vol>0,(bad,vol);bm.to_mesh(o.data);bm.free();o.data.update();return vol
protected=sorted(tuple(v.co) for v in body.data.vertices if v.co.x<25 or v.co.y<.75 or v.co.z<3.19)
def smooth(t):
 t=max(0,min(1,t));return t*t*(3-2*t)
def floor(x,y):return 3.2+5.4*(1-smooth((x-26)/3.5)*smooth((y-1)/1.5))
# Closed height-field cutter. The lower face slopes smoothly to the thin land.
xs=[25+i*.2 for i in range(101)];ys=[i*.2 for i in range(101)];nx=len(xs);ny=len(ys)
vs=[(x,y,floor(x,y)) for y in ys for x in xs];fs=[]
for j in range(ny-1):
 for i in range(nx-1):
  a=j*nx+i;fs.append((a,a+nx,a+nx+1,a+1))
border=list(range(nx))+[j*nx+nx-1 for j in range(1,ny)]+[(ny-1)*nx+i for i in range(nx-2,-1,-1)]+[j*nx for j in range(ny-2,0,-1)]
top=[]
for idx in border:top.append(len(vs));x,y,z=vs[idx];vs.append((x,y,20))
for i in range(len(border)):
 k=(i+1)%len(border);fs.append((border[i],border[k],top[k],top[i]))
fs.append(tuple(top));me=bpy.data.meshes.new('Rebaixo superior');me.from_pydata(vs,[],fs);me.update();cut=bpy.data.objects.new('Corte local argola',me);bpy.context.collection.objects.link(cut);validated(cut)
active(body);m=body.modifiers.new('Rebaixo local 3.2mm','BOOLEAN');m.operation='DIFFERENCE';m.solver='EXACT';m.object=cut;bpy.ops.object.modifier_apply(modifier=m.name);bpy.data.objects.remove(cut,do_unlink=True)
assert protected==sorted(tuple(v.co) for v in body.data.vertices if v.co.x<25 or v.co.y<.75 or v.co.z<3.19),'Protected geometry changed'
volume=validated(body);validated(text)
for x,y in [(35,5),(32,8),(35,7)]:
 ok,p,n,i=body.ray_cast(Vector((x,y,20)),Vector((0,0,-1)));assert ok and abs(p.z-3.2)<.01,(x,y,p)
for x in [-34,-30,-26,-22]:
 for y in [-7,6]:
  hits=[]
  for dz in [-1,1]:
   ok,p,n,i=body.ray_cast(Vector((x,y,3.2)),Vector((0,0,dz)));assert ok;hits.append(p.z)
  assert hits[1]-hits[0]>=2.19
for o,name in [(body,'Chave_corpo_PETG_metal_rebaixo.stl'),(text,'Chave_site_PETG_preto.stl')]:
 active(o);bpy.ops.wm.stl_export(filepath=os.path.join(R,name),export_selected_objects=True)
mesh=['<?xml version="1.0"?><model unit="millimeter" xmlns="http://schemas.microsoft.com/3dmanufacturing/core/2015/02"><resources>']
for oid,o in [(1,body),(2,text)]:
 mesh.append(f'<object id="{oid}" type="model"><mesh><vertices>');mesh.extend(f'<vertex x="{v.co.x:.6f}" y="{v.co.y:.6f}" z="{v.co.z:.6f}"/>' for v in o.data.vertices);mesh.append('</vertices><triangles>');mesh.extend(f'<triangle v1="{p.vertices[0]}" v2="{p.vertices[1]}" v3="{p.vertices[2]}"/>' for p in o.data.polygons);mesh.append('</triangles></mesh></object>')
mesh.append('</resources></model>')
for suffix,out in [('', 'Chave_78mm_rebaixo_argola_01.3mf'),('_10_unidades','Chave_78mm_rebaixo_argola_10.3mf')]:
 with zipfile.ZipFile(os.path.join(SOURCE,'Chave_78mm_texto_maior'+suffix+'.3mf')) as src,zipfile.ZipFile(os.path.join(R,out),'w',zipfile.ZIP_DEFLATED) as dst:
  for name in src.namelist():
   data=src.read(name)
   if name=='3D/Objects/object_1.model':data=''.join(mesh).encode()
   elif name=='Metadata/model_settings.config':
    root=E.fromstring(data)
    for o in root.findall('object'):
     for meta in o.findall('metadata'):
      if meta.get('key')=='name':meta.set('value','Chave 78mm rebaixo argola 3.2mm')
     for part in o.findall('part'):
      stat=part.find('mesh_stat')
      if stat is not None:stat.set('face_count',str(len(body.data.polygons) if part.get('id')=='1' else len(text.data.polygons)))
    data=E.tostring(root,encoding='utf-8',xml_declaration=True)
   elif name.endswith(('.gcode','.md5','.png')) or name=='Metadata/slice_info.config':continue
   dst.writestr(name,data)
s=bpy.context.scene;s.render.engine='BLENDER_WORKBENCH';s.display.shading.light='STUDIO';s.display.shading.color_type='MATERIAL';s.display.shading.show_shadows=True;s.display.shading.show_cavity=True;s.display.shading.cavity_type='BOTH';s.render.resolution_x=1400;s.render.resolution_y=900;s.render.resolution_percentage=100
body.data.materials.clear();mat=bpy.data.materials.new('PETG metal');mat.diffuse_color=(.5,.53,.58,1);body.data.materials.append(mat)
for o in s.objects:
 if o.type=='MESH' and o not in (body,text):o.hide_render=True
c=s.camera;c.location=(12,-60,105);c.rotation_euler=(Vector((0,0,3.5))-c.location).to_track_quat('-Z','Y').to_euler();c.data.ortho_scale=87;s.render.filepath=os.path.join(R,'Previa_modelo_real.png');bpy.ops.render.render(write_still=True)
bpy.ops.wm.save_as_mainfile(filepath=os.path.join(R,'Chave_78mm_rebaixo_argola.blend'))
report=dict(length_mm=body.dimensions.x,land_thickness_mm=3.2,nonmanifold_edges=0,body_volume_mm3=volume,protected_vertices=len(protected),text_preserved=True,channel_clearance_preserved=True,physical_chain_fit_tested=False)
# Single-material STL, joining the lettering to the body by exact solid union.
active(body);m=body.modifiers.new('Unir texto STL monocor','BOOLEAN');m.operation='UNION';m.solver='EXACT';m.object=text;bpy.ops.object.modifier_apply(modifier=m.name);report['single_material_volume_mm3']=validated(body);bpy.ops.wm.stl_export(filepath=os.path.join(R,'Chave_78mm_rebaixo_argola_completa.stl'),export_selected_objects=True)
json.dump(report,open(os.path.join(R,'validacao.json'),'w'),indent=2);print('DONE',report,flush=True)
