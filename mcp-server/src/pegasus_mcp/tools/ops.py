"""Operational tools — invoke ERP self-test scripts via PHP CLI."""

from __future__ import annotations

import os
import subprocess
from typing import Any


def _run_php_script(script_relpath: str, timeout: int = 120) -> dict[str, Any]:
    root = os.getenv("PEGASUS_ROOT", ".")
    php = os.getenv("PHP_BIN", "php")
    target = os.path.join(root, script_relpath)
    if not os.path.exists(target):
        return {"ok": False, "error": f"script not found: {target}"}
    try:
        proc = subprocess.run(
            [php, target],
            cwd=root,
            capture_output=True,
            text=True,
            timeout=timeout,
            encoding="utf-8",
            errors="replace",
        )
    except subprocess.TimeoutExpired:
        return {"ok": False, "error": f"timeout after {timeout}s"}
    return {
        "ok": proc.returncode == 0,
        "exit_code": proc.returncode,
        "stdout": proc.stdout,
        "stderr": proc.stderr,
    }


def run_system_test() -> dict[str, Any]:
    """Run the 164-item PEGASUS System Test (database/system_test.php)."""
    return _run_php_script("database/system_test.php", timeout=180)


def run_deep_audit() -> dict[str, Any]:
    """Run the deep audit script (database/deep_audit.php) — 6 sections."""
    return _run_php_script("database/deep_audit.php", timeout=120)


TOOLS = [
    {
        "name": "pegasus_run_system_test",
        "description": (
            "Execute the PEGASUS System Test (164 automated checks across DB, "
            "master data, HTTP routes). Returns stdout/stderr."
        ),
        "inputSchema": {"type": "object", "properties": {}},
        "handler": run_system_test,
    },
    {
        "name": "pegasus_run_deep_audit",
        "description": (
            "Execute the PEGASUS deep audit (master data quality / referential "
            "integrity / business workflow / security / schema / performance)."
        ),
        "inputSchema": {"type": "object", "properties": {}},
        "handler": run_deep_audit,
    },
]
