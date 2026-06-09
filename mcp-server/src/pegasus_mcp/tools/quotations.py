"""Quotation-related MCP tools (read-only)."""

from __future__ import annotations

from typing import Any

from ..db import query, query_one


VALID_STATUSES = {
    "DRAFT", "INTERNAL_REVIEW", "PENDING_APPROVAL", "AWAIT_APPROVAL",
    "APPROVED", "REJECTED", "SUBMITTED", "NEGOTIATING", "WON", "LOST",
    "EXPIRED", "CANCELLED",
}


def list_quotations(
    status: str | None = None,
    customer_code: str | None = None,
    days: int = 30,
    limit: int = 50,
) -> dict[str, Any]:
    """List quotations with optional filters (created within last N days)."""
    limit = max(1, min(int(limit or 50), 500))
    days = max(1, min(int(days or 30), 365))
    where = ["q.is_deleted = false", f"q.created_at >= NOW() - INTERVAL '{days} days'"]
    params: list[Any] = []
    if status:
        if status.upper() not in VALID_STATUSES:
            return {
                "error": f"invalid status: {status}",
                "valid_statuses": sorted(VALID_STATUSES),
            }
        where.append("q.status = %s")
        params.append(status.upper())
    if customer_code:
        where.append("c.customer_code = %s")
        params.append(customer_code)

    sql = f"""
        SELECT q.quotation_no, c.customer_code, c.customer_name,
               q.status, q.grand_total_thb, q.subtotal_thb, q.vat_rate,
               q.issue_date, q.expiry_date, q.created_at
        FROM quotation_headers q
        LEFT JOIN customers c ON c.customer_id = q.customer_id
        WHERE {' AND '.join(where)}
        ORDER BY q.created_at DESC
        LIMIT {limit}
    """
    rows = query(sql, tuple(params))
    return {"count": len(rows), "limit": limit, "days": days, "rows": rows}


def get_quotation(quotation_no: str) -> dict[str, Any]:
    """Get a single quotation with line items."""
    header = query_one(
        """
        SELECT q.quotation_no, q.revision_no, q.status, q.issue_date, q.expiry_date,
               q.currency_code, q.exchange_rate, q.subtotal_thb, q.discount_amount,
               q.vat_rate, q.vat_amount, q.grand_total_thb,
               q.project_name, q.project_code,
               c.customer_code, c.customer_name
        FROM quotation_headers q
        LEFT JOIN customers c ON c.customer_id = q.customer_id
        WHERE q.quotation_no = %s AND q.is_deleted = false
        """,
        (quotation_no,),
    )
    if not header:
        return {"found": False, "quotation_no": quotation_no}
    lines = query(
        """
        SELECT l.line_no, l.parent_line_no, l.is_category_row,
               l.item_description, l.quantity, l.unit, l.unit_price,
               l.discount_rate, l.ext_price
        FROM quotation_lines l
        JOIN quotation_headers q ON q.quotation_id = l.quotation_id
        WHERE q.quotation_no = %s AND l.is_deleted = false
        ORDER BY l.sort_order, l.line_no
        """,
        (quotation_no,),
    )
    return {"found": True, "header": header, "lines": lines}


def pending_approvals() -> dict[str, Any]:
    """Snapshot of all four approval queues (counts + samples)."""
    out: dict[str, Any] = {}
    # quotations gate by status; everything else has its own approval_status column
    queues = [
        ("customers", "customers", "customer_code",
         "approval_status='PENDING' AND is_current = true AND is_deleted = false"),
        ("suppliers", "suppliers", "supplier_code",
         "approval_status='PENDING' AND is_current = true AND is_deleted = false"),
        ("quotations", "quotation_headers", "quotation_no",
         "status IN ('PENDING_APPROVAL','AWAIT_APPROVAL') AND is_deleted = false"),
        ("purchase_orders", "purchase_order_headers", "po_no",
         "approval_status='PENDING' AND is_deleted = false"),
    ]
    for entity, table, code_col, where in queues:
        try:
            cnt = query_one(f"SELECT COUNT(*) AS n FROM {table} WHERE {where}")
            sample = query(
                f"SELECT {code_col} AS code FROM {table} "
                f"WHERE {where} ORDER BY created_at DESC LIMIT 5"
            )
            out[entity] = {"pending": cnt["n"] if cnt else 0, "sample": sample}
        except Exception as e:  # noqa: BLE001
            out[entity] = {"error": str(e)}
    return out


TOOLS = [
    {
        "name": "pegasus_list_quotations",
        "description": "List PEGASUS quotations filtered by status / customer_code / recency. Read-only.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "status": {"type": "string", "description": "DRAFT/SUBMITTED/WON/LOST/etc."},
                "customer_code": {"type": "string"},
                "days": {"type": "integer", "default": 30, "minimum": 1, "maximum": 365},
                "limit": {"type": "integer", "default": 50, "minimum": 1, "maximum": 500},
            },
        },
        "handler": list_quotations,
    },
    {
        "name": "pegasus_get_quotation",
        "description": "Get one quotation header + lines (by quotation_no).",
        "inputSchema": {
            "type": "object",
            "properties": {
                "quotation_no": {"type": "string", "description": "e.g. QT-20260422-0001"},
            },
            "required": ["quotation_no"],
        },
        "handler": get_quotation,
    },
    {
        "name": "pegasus_pending_approvals",
        "description": (
            "Snapshot of the four approval queues (customers/suppliers/quotations/POs) "
            "— counts and 5 most recent codes for each."
        ),
        "inputSchema": {"type": "object", "properties": {}},
        "handler": pending_approvals,
    },
]
