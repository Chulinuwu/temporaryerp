-- Remove leading circled number prefixes (①②③... etc.) from deal_statuses.status_name
-- Also strips leading whitespace after the prefix.

-- Preview the affected rows first (optional; run SELECT before UPDATE)
-- SELECT status_id, sort_order, status_name
-- FROM deal_statuses
-- WHERE status_name ~ '^[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]'
-- ORDER BY sort_order;

-- ① Strip the circled-number prefix + whitespace
UPDATE deal_statuses
SET status_name = regexp_replace(status_name, '^[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]\s*', ''),
    status_name_jp = regexp_replace(COALESCE(status_name_jp, ''), '^[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]\s*', ''),
    status_name_th = regexp_replace(COALESCE(status_name_th, ''), '^[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]\s*', '')
WHERE status_name ~ '^[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]'
   OR status_name_jp ~ '^[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]'
   OR status_name_th ~ '^[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]';

-- ② Handle duplicates: after prefix removal, you may now have two rows with the same status_name
-- (e.g. "Appointment" existed both with and without the prefix). Merge by keeping one.
-- First, check duplicates:
SELECT status_name, COUNT(*) AS n, array_agg(status_id ORDER BY status_id) AS ids
FROM deal_statuses
WHERE is_deleted = FALSE OR is_deleted IS NULL
GROUP BY status_name
HAVING COUNT(*) > 1;

-- ③ To remove the duplicate rows (keep the LOWEST status_id in each group),
-- run the following (uncomment after reviewing the above SELECT result):
-- DELETE FROM deal_statuses
-- WHERE status_id IN (
--     SELECT status_id FROM (
--         SELECT status_id,
--                ROW_NUMBER() OVER (PARTITION BY status_name ORDER BY status_id) AS rn
--         FROM deal_statuses
--     ) t WHERE rn > 1
-- );

-- ④ Verify results
SELECT sort_order, status_name, status_name_jp
FROM deal_statuses
ORDER BY sort_order, status_id;
