-- Rename SALES_MANAGER role label to just "Manager" / マネージャー / ผู้จัดการ
UPDATE roles
SET role_name    = 'Manager',
    role_name_jp = 'マネージャー',
    role_name_th = 'ผู้จัดการ'
WHERE role_code = 'SALES_MANAGER';

SELECT role_code, role_name, role_name_jp, role_name_th FROM roles ORDER BY role_code;
