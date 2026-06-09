"""Smoke test for PEGASUS MCP — bypass MCP transport, call handlers directly."""
import json
import os
import sys

# Ensure UTF-8 on Windows console
sys.stdout.reconfigure(encoding="utf-8")

from dotenv import load_dotenv
load_dotenv()

# Path setup
sys.path.insert(0, os.path.join(os.path.dirname(__file__), "src"))

from pegasus_mcp.tools import customers, kpi, ops, quotations  # noqa: E402

PASS = 0
FAIL = 0


def check(label: str, fn, *args, **kwargs):
    global PASS, FAIL
    try:
        result = fn(*args, **kwargs)
        if isinstance(result, dict) and result.get("error"):
            raise RuntimeError(result["error"])
        # Sanity: result must be JSON-serializable
        json.dumps(result, default=str, ensure_ascii=False)
        print(f"  [PASS] {label}")
        PASS += 1
        return result
    except Exception as e:  # noqa: BLE001
        print(f"  [FAIL] {label}: {e}")
        FAIL += 1
        return None


print("=== A. Customers ===")
r = check("search_customers (limit=3)", customers.search_customers, limit=3)
if r:
    print(f"        -> {r['count']} rows")
r = check("search_customers keyword=FUTABA", customers.search_customers, keyword="FUTABA")
if r:
    print(f"        -> {r['count']} rows")
    if r["count"]:
        first_code = r["rows"][0]["customer_code"]
        r2 = check(f"get_customer {first_code}", customers.get_customer, first_code)
        if r2 and r2.get("found"):
            print(f"        -> recent={len(r2['recent_quotations'])} open_ar_cnt={r2['open_ar']['cnt']}")

print("\n=== B. Quotations ===")
r = check("list_quotations status=WON limit=3 days=365", quotations.list_quotations,
          status="WON", limit=3, days=365)
if r:
    print(f"        -> {r['count']} rows")
# Invalid-status path returns an error dict by design; treat as expected.
_r = quotations.list_quotations(status="BOGUS")
if isinstance(_r, dict) and _r.get("error", "").startswith("invalid status"):
    print("  [PASS] list_quotations rejects invalid status (expected)"); PASS += 1
else:
    print("  [FAIL] list_quotations should reject invalid status"); FAIL += 1
r = check("pending_approvals", quotations.pending_approvals)
if r:
    print(f"        -> {r}")

print("\n=== C. KPI ===")
r = check("kpi_dashboard", kpi.kpi_dashboard)
if r:
    print(f"        -> year={r['year']} count={r['count']}")
r = check("pipeline_summary", kpi.pipeline_summary)
if r:
    print(f"        -> {len(r['by_status'])} statuses, {len(r['top_customers'])} top customers")
r = check("ar_aging", kpi.ar_aging)
if r:
    print(f"        -> {r}")

print("\n=== D. Ops ===")
# Just verify it can locate the script (don't actually run system_test in smoke - it's slow)
import subprocess
php_ok = subprocess.run(["php", "--version"], capture_output=True, text=True)
if php_ok.returncode == 0:
    r = check("run_deep_audit", ops.run_deep_audit)
    if r:
        print(f"        -> exit={r['exit_code']} stdout_len={len(r['stdout'])}")
else:
    print("  [SKIP] PHP not available")

print(f"\n=== SMOKE TEST: {PASS} passed, {FAIL} failed ===")
sys.exit(0 if FAIL == 0 else 1)
