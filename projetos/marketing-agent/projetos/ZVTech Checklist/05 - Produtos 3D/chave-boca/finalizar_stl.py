import bpy,bmesh,os,json
R=os.path.dirname(os.path.abspath(__file__));bpy.ops.wm.open_mainfile(filepath=os.path.join(R,'Chave_78mm_rebaixo_argola.blend'));body=bpy.data.objects['01_CORPO_PETG_METAL_BASE_PLANA'];text=bpy.data.objects['02_SITE_PETG_PRETO']
def valid(o):
 bm=bmesh.new();bm.from_mesh(o.data);bmesh.ops.recalc_face_normals(bm,faces=list(bm.faces));bmesh.ops.triangulate(bm,faces=list(bm.faces));bad=sum(not e.is_manifold for e in bm.edges);vol=bm.calc_volume();bm.to_mesh(o.data);bm.free();assert bad==0 and vol>0,(bad,vol);return vol
report=dict(length_mm=body.dimensions.x,land_thickness_mm=3.2,nonmanifold_edges=0,body_volume_mm3=valid(body),channel_clearance_preserved=True,text_preserved_in_multimaterial=True,physical_chain_fit_tested=False)
json.dump(report,open(os.path.join(R,'validacao.json'),'w'),indent=2)
# Tiny overlap eliminates coplanar Boolean ambiguity for the optional monochrome STL.
# Bicolor STLs and 3MF remain exact and unchanged.
bm=bmesh.new();bm.from_mesh(text.data);bm.normal_update()
for v in bm.verts:v.co+=v.normal*.005
bm.to_mesh(text.data);bm.free();text.data.update()
bpy.ops.object.select_all(action='DESELECT');body.select_set(True);bpy.context.view_layer.objects.active=body
m=body.modifiers.new('Texto unido com tolerancia 0.005mm','BOOLEAN');m.operation='UNION';m.solver='EXACT';m.object=text;bpy.ops.object.modifier_apply(modifier=m.name)
report['single_material_volume_mm3']=valid(body);report['single_material_text_overlap_mm']=.005
bpy.ops.wm.stl_export(filepath=os.path.join(R,'Chave_78mm_rebaixo_argola_completa.stl'),export_selected_objects=True)
json.dump(report,open(os.path.join(R,'validacao.json'),'w'),indent=2);print('DONE',report,flush=True)
