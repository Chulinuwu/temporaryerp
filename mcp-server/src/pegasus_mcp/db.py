"""PostgreSQL connection layer for PEGASUS MCP.

Uses a connection pool from psycopg3. The connecting role SHOULD be
read-only (`pegasus_mcp_ro`) so that any tool exposed via MCP cannot
mutate ERP data accidentally. Write operations must go through the
PEGASUS HTTP API (api.py) which enforces RBAC and audit logging.
"""

from __future__ import annotations

import logging
import os
from contextlib import contextmanager
from typing import Any, Iterator

import psycopg
from psycopg.rows import dict_row

log = logging.getLogger(__name__)


def _dsn() -> str:
    return (
        f"host={os.getenv('PG_HOST', 'localhost')} "
        f"port={os.getenv('PG_PORT', '5432')} "
        f"dbname={os.getenv('PG_DATABASE', 'pegasus_erp')} "
        f"user={os.getenv('PG_USER', 'pegasus_mcp_ro')} "
        f"password={os.getenv('PG_PASSWORD', '')} "
        f"application_name=pegasus_mcp"
    )


@contextmanager
def get_conn() -> Iterator[psycopg.Connection]:
    """Yield a short-lived connection (autocommit, read-only)."""
    conn = psycopg.connect(_dsn(), autocommit=True, row_factory=dict_row)
    try:
        # Belt-and-suspenders: enforce read-only at session level.
        conn.execute("SET default_transaction_read_only = on")
        conn.execute("SET statement_timeout = '15s'")
        yield conn
    finally:
        conn.close()


def query(sql: str, params: tuple | dict | None = None) -> list[dict[str, Any]]:
    """Run a SELECT and return rows as a list of dicts."""
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params or ())
            try:
                return cur.fetchall()
            except psycopg.ProgrammingError:
                return []


def query_one(sql: str, params: tuple | dict | None = None) -> dict[str, Any] | None:
    rows = query(sql, params)
    return rows[0] if rows else None
