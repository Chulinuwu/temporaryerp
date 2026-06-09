"""Customer-related MCP tools."""

from __future__ import annotations

from typing import Any

from ..db import query, query_one


def search_customers(
    keyword: str | None = None,
    country: str | None = None,
    limit: int = 20,
) -> dict[str, Any]:
    """Search customers by name/code/contact, optionally filtered by country code.

    Args:
        keyword: Substring matched against customer_code / customer_name(_jp/_th) / contact_person.
        country: Optional 2-letter country filter (e.g. 'TH', 'JP').
        limit: Max rows (default 20, cap 200).
    """
    limit = max(1, min(int(limit or 20), 200))
    where = ["c.is_current = true", "c.is_deleted = false"]
    params: list[Any] = []
    if keyword:
        where.append(
            "(c.customer_code ILIKE %s OR c.customer_name ILIKE %s "
            "OR COALESCE(c.customer_name_jp,'') ILIKE %s "
            "OR COALESCE(c.customer_name_th,'') ILIKE %s "
            "OR COALESCE(c.contact_person,'') ILIKE %s)"
        )
        like = f"%{keyword}%"
        params.extend([like, like, like, like, like])
    if country:
        where.append("c.country = %s")
        params.append(country.upper())

    sql = f"""
        SELECT c.customer_code, c.customer_name, c.customer_name_jp,
               c.country, c.contact_person, c.email, c.phone, c.tax_id,
               c.payment_terms, c.currency_code, c.approval_status
        FROM customers c
        WHERE {' AND '.join(where)}
        ORDER BY c.customer_code
        LIMIT {limit}
    """
    rows = query(sql, tuple(params))
    return {"count": len(rows), "limit": limit, "rows": rows}


def get_customer(customer_code: str) -> dict[str, Any]:
    """Get one customer with recent quotations + open AR balance."""
    base = query_one(
        """
        SELECT customer_id, customer_code, customer_name, customer_name_jp,
               customer_name_th, country, contact_person, email, phone,
               address, tax_id, payment_terms, credit_limit, currency_code,
               approval_status
        FROM customers
        WHERE customer_code = %s AND is_current = true AND is_deleted = false
        """,
        (customer_code,),
    )
    if not base:
        return {"found": False, "customer_code": customer_code}

    quotations = query(
        """
        SELECT quotation_no, status, grand_total_thb, issue_date
        FROM quotation_headers
        WHERE customer_id = %s AND is_deleted = false
        ORDER BY issue_date DESC
        LIMIT 10
        """,
        (base["customer_id"],),
    )
    open_ar = query_one(
        """
        SELECT COUNT(*) AS cnt,
               COALESCE(SUM(balance_thb), 0) AS open_balance
        FROM ar_invoices
        WHERE customer_id = %s
          AND COALESCE(balance_thb, 0) > 0
        """,
        (base["customer_id"],),
    )
    return {
        "found": True,
        "customer": base,
        "recent_quotations": quotations,
        "open_ar": open_ar,
    }


TOOLS = [
    {
        "name": "pegasus_search_customers",
        "description": (
            "Search PEGASUS ERP customers by keyword (code/name in JA/EN/TH/contact). "
            "Optional country filter (e.g. 'TH'). Returns up to `limit` rows."
        ),
        "inputSchema": {
            "type": "object",
            "properties": {
                "keyword": {"type": "string", "description": "Substring to match"},
                "country": {"type": "string", "description": "2-letter country (TH/JP/...)"},
                "limit": {"type": "integer", "default": 20, "minimum": 1, "maximum": 200},
            },
        },
        "handler": search_customers,
    },
    {
        "name": "pegasus_get_customer",
        "description": (
            "Get full details for one customer by customer_code, plus 10 recent "
            "quotations and open AR (THB)."
        ),
        "inputSchema": {
            "type": "object",
            "properties": {
                "customer_code": {"type": "string", "description": "e.g. CUS-0001 or CUS-FUTABA"},
            },
            "required": ["customer_code"],
        },
        "handler": get_customer,
    },
]
