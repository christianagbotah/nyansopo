import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { canAny } from "@/lib/rbac";
import { normalizeTheme, DEFAULT_THEME, THEME_PRESETS } from "@/lib/theme";

/* ── CI3 Admin.php theme_settings parity ──
   GET  → the school's current theme (every signed-in member needs it to paint the shell)
   POST → apply_theme (preset) | save_custom (three pickers) — settings.theme only:
          the colour scheme is the school's super admin / proprietor's call ── */

const THEME_PRESET_MAP = Object.fromEntries(THEME_PRESETS.map((p) => [p.key, p]));

function safeParse(v: string): unknown {
  try { return JSON.parse(v); } catch { return null; }
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  const { id } = await params;
  if (!(await canOperateSchool(user, id))) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  const row = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId: id, key: "theme" } } });
  const theme = normalizeTheme(row ? safeParse(row.value) : null) ?? DEFAULT_THEME;
  return NextResponse.json({ theme });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  const { id } = await params;
  if (!(await canOperateSchool(user, id))) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  if (!canAny(user.permissions, ["settings.theme"])) {
    return NextResponse.json({ error: "Forbidden — only the school's super admin can change the colour scheme" }, { status: 403 });
  }

  const body = await req.json().catch(() => ({}));
  const action = body.action as "preset" | "custom" | undefined;

  let theme;
  if (action === "preset") {
    const preset = THEME_PRESET_MAP[body.theme as string];
    if (!preset) return NextResponse.json({ error: "Invalid theme" }, { status: 400 });
    theme = { name: preset.name, primary: preset.primary, secondary: preset.secondary, accent: preset.accent };
  } else if (action === "custom") {
    theme = normalizeTheme({ name: body.name || "Custom", primary: body.primary, secondary: body.secondary, accent: body.accent });
    if (!theme) return NextResponse.json({ error: "Pick all three colours (primary, secondary, accent)" }, { status: 400 });
  } else {
    return NextResponse.json({ error: "Unknown action" }, { status: 400 });
  }

  await db.schoolSetting.upsert({
    where: { schoolId_key: { schoolId: id, key: "theme" } },
    update: { value: JSON.stringify(theme) },
    create: { schoolId: id, key: "theme", value: JSON.stringify(theme) },
  });
  return NextResponse.json({ theme, message: `Theme applied — ${theme.name}` });
}
