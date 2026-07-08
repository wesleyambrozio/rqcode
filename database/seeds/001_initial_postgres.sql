insert into admin_users (name, email, password_hash, role, active)
values ('Administrador', 'admin@central.local', '$2y$10$aFv1sdCp40DrZQDnKJC/D.FVfs5qJTHRwn1E5bzG36yS.K3QS.uzG', 'owner', true);

insert into vendors (name, email, commission_default_percent, active)
values ('Vendedor exemplo', 'vendedor@central.local', 10.00, true);

insert into saas_systems (name, slug, base_url, database_type, active)
values ('Sistema exemplo', 'sistema-exemplo', 'https://app.exemplo.com', 'supabase', true);

insert into plans (system_id, name, billing_cycle, price, active)
values (1, 'Plano Mensal', 'monthly', 99.90, true);

insert into metric_snapshots (system_id, snapshot_date, accounts_total, new_accounts, active_users, online_users, pending_payments, paid_payments)
values (1, current_date, 120, 4, 86, 12, 8, 52);
