"""KPI / analytics MCP tools."""

from __future__ import annotations

from typing import Any

from ..db import query, query_one


def kpi_dashboard(year: int | None = None) -> dict[str, Any]:
    """Annual sales KPI rollup per employee (target vs WON quotation total)."""
    if year is None:
        row = query_one("SELECT EXTRACT(YEAR FROM CURRENT_DATE)::int AS y")
        year = row["y"] if row else 2026

    targets = query(
        """
        SELECT t.employee_id,
               COALESCE(e.full_name_jp, e.full_name_th) AS employee_name,
               t.fiscal_year,
               t.annual_profit_target,
               t.profit_per_order,
               t.close_rate_pct,
               t.appt_rate_pct,
               t.annual_order_target,
               t.annual_meeting_target,
               t.annual_contact_target
        FROM sales_kpi_targets t
        LEFT JOIN employees e ON e.employee_id = t.employee_id
        WHERE t.fiscal_year = %s
        ORDER BY t.employee_id
        """,
        (year,),
    )

    achievements = query(
        """
        SELECT q.quoted_by AS employee_id,
               COUNT(*) AS won_quotations,
               COALESCE(SUM(q.grand_total_thb), 0) AS won_total
        FROM quotation_headers q
        WHERE q.status = 'WON'
          AND q.is_deleted = false
          AND EXTRACT(YEAR FROM q.issue_date) = %s
        GROUP BY q.quoted_by
        """,
        (year,),
    )
    ach_by_emp = {a["employee_id"]: a for a in achievements}

    rollup = []
    for t in targets:
        a = ach_by_emp.get(t["employee_id"], {"won_quotations": 0, "won_total": 0})
        target = float(t["annual_profit_target"] or 0)
        actual = float(a["won_total"] or 0)
        rollup.append({
            **t,
            "won_quotations": a["won_quotations"],
            "won_total_thb": actual,
            "achievement_pct": round((actual / target) * 100, 2) if target else None,
        })

    return {"year": year, "rollup": rollup, "count": len(rollup)}


def pipeline_summary() -> dict[str, Any]:
    """Pipeline by deal status (joined to deal_statuses) + top 10 customers."""
    by_status = query(
        """
        SELECT s.status_name, s.win_pct,
               COUNT(d.deal_id) AS cnt,
               COALESCE(SUM(d.expected_amount), 0) AS total
        FROM deals d
        LEFT JOIN deal_statuses s ON s.status_id = d.status_id
        WHERE d.is_deleted = false
        GROUP BY s.status_name, s.win_pct, s.sort_order
        ORDER BY s.sort_order NULLS LAST
        """
    )
    top_customers = query(
        """
        SELECT c.customer_code, c.customer_name,
               COUNT(d.deal_id) AS deals,
               COALESCE(SUM(d.expected_amount), 0) AS total
        FROM deals d
        JOIN customers c ON c.customer_id = d.customer_id
        WHERE d.is_deleted = false AND c.is_current = true
        GROUP BY c.customer_code, c.customer_name
        ORDER BY total DESC
        LIMIT 10
        """
    )
    return {"by_status": by_status, "top_customers": top_customers}


def ar_aging() -> dict[str, Any]:
    """AR aging buckets (current / 1-30 / 31-60 / 61-90 / 90+) — THB."""
    rows = query(
        """
        SELECT
          COALESCE(SUM(CASE WHEN due_date >= CURRENT_DATE
                            THEN balance_thb ELSE 0 END), 0) AS current_,
          COALESCE(SUM(CASE WHEN CURRENT_DATE - due_date BETWEEN 1 AND 30
                            THEN balance_thb ELSE 0 END), 0) AS d1_30,
          COALESCE(SUM(CASE WHEN CURRENT_DATE - due_date BETWEEN 31 AND 60
                            THEN balance_thb ELSE 0 END), 0) AS d31_60,
          COALESCE(SUM(CASE WHEN CURRENT_DATE - due_date BETWEEN 61 AND 90
                            THEN balance_thb ELSE 0 END), 0) AS d61_90,
          COALESCE(SUM(CASE WHEN CURRENT_DATE - due_date > 90
                            THEN balance_thb ELSE 0 END), 0) AS d90_plus,
          COUNT(*) FILTER (WHERE COALESCE(balance_thb, 0) > 0) AS open_invoices
        FROM ar_invoices
        WHERE COALESCE(balance_thb, 0) > 0
        """
    )
    return rows[0] if rows else {}


TOOLS = [
    {
        "name": "pegasus_kpi_dashboard",
        "description": "Annual sales KPI rollup per employee — target vs WON quotation total (THB).",
        "inputSchema": {
            "type": "object",
            "properties": {
                "year": {"type": "integer", "description": "Fiscal year (default: current)"},
            },
        },
        "handler": kpi_dashboard,
    },
    {
        "name": "pegasus_pipeline",
        "description": "Sales pipeline summary by deal status + top 10 customers by deal value.",
        "inputSchema": {"type": "object", "properties": {}},
        "handler": pipeline_summary,
    },
    {
        "name": "pegasus_ar_aging",
        "description": "AR aging analysis bucketed (current/1-30/31-60/61-90/90+) in THB.",
        "inputSchema": {"type": "object", "properties": {}},
        "handler": ar_aging,
    },
]
