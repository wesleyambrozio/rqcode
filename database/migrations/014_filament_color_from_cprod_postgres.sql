update filaments_3d f
set color = left(trim(i.supplier_product_code), 80)
from fiscal_invoice_items i
where i.id = f.fiscal_invoice_item_id
  and i.is_filament = true
  and trim(coalesce(i.supplier_product_code, '')) <> '';
