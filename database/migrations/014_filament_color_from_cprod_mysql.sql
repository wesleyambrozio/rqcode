update filaments_3d f
join fiscal_invoice_items i on i.id = f.fiscal_invoice_item_id
set f.color = left(trim(i.supplier_product_code), 80)
where i.is_filament = 1
  and trim(coalesce(i.supplier_product_code, '')) <> '';
