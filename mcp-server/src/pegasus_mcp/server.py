"""PEGASUS ERP — MCP Server entrypoint.

Uses the official `mcp` Python SDK with stdio transport. Tools are
collected from each module in `pegasus_mcp.tools` (each module exposes
a `TOOLS` list of {name, description, inputSchema, handler}).

Run:
    python -m pegasus_mcp
"""

from __future__ import annotations

import asyncio
import json
import logging
import os
from typing import Any

from dotenv import load_dotenv

from .tools import customers, kpi, ops, quotations

# Load .env from project root or cwd.
load_dotenv()

logging.basicConfig(
    level=os.getenv("LOG_LEVEL", "INFO"),
    format="%(asctime)s %(levelname)s %(name)s: %(message)s",
)
log = logging.getLogger("pegasus_mcp")


def _collect_tools() -> dict[str, dict[str, Any]]:
    registry: dict[str, dict[str, Any]] = {}
    for mod in (customers, quotations, kpi, ops):
        for t in getattr(mod, "TOOLS", []):
            registry[t["name"]] = t
    return registry


async def _serve() -> None:
    # Imports are local so that `--help` / `--list-tools` don't require the SDK.
    from mcp.server import Server
    from mcp.server.stdio import stdio_server
    from mcp.types import TextContent, Tool

    registry = _collect_tools()
    server: Server = Server("pegasus-erp")

    @server.list_tools()
    async def list_tools() -> list[Tool]:
        return [
            Tool(
                name=t["name"],
                description=t["description"],
                inputSchema=t["inputSchema"],
            )
            for t in registry.values()
        ]

    @server.call_tool()
    async def call_tool(name: str, arguments: dict[str, Any] | None) -> list[TextContent]:
        tool = registry.get(name)
        if not tool:
            return [TextContent(type="text", text=json.dumps({"error": f"unknown tool: {name}"}))]
        handler = tool["handler"]
        try:
            args = arguments or {}
            # Tools are sync; run in thread to avoid blocking the event loop.
            result = await asyncio.to_thread(handler, **args)
            text = json.dumps(result, default=str, ensure_ascii=False, indent=2)
            return [TextContent(type="text", text=text)]
        except Exception as e:  # noqa: BLE001 - return error to client
            log.exception("tool %s failed", name)
            return [TextContent(
                type="text",
                text=json.dumps({"error": str(e), "tool": name}, ensure_ascii=False),
            )]

    log.info("pegasus-mcp starting; %d tools registered", len(registry))
    async with stdio_server() as (read, write):
        await server.run(read, write, server.create_initialization_options())


def list_tools_cli() -> None:
    """Print registered tools as JSON (no SDK required)."""
    out = []
    for t in _collect_tools().values():
        out.append({
            "name": t["name"],
            "description": t["description"],
            "inputSchema": t["inputSchema"],
        })
    print(json.dumps(out, ensure_ascii=False, indent=2))


def main() -> None:
    import sys
    if "--list-tools" in sys.argv:
        list_tools_cli()
        return
    if "--help" in sys.argv or "-h" in sys.argv:
        print("Usage: python -m pegasus_mcp [--list-tools]")
        print("       (default: run MCP stdio server)")
        return
    asyncio.run(_serve())


if __name__ == "__main__":
    main()
