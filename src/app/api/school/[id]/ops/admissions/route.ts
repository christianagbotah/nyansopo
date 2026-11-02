import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { invoicePaymentReceipt } from "@/lib/receipt-payload";
import { nextInvoiceNo, nextReceiptNo, nextStudentIndex } from "@/lib/codes";
import { storeMedia } from "@/lib/media";

// ═══════════════════════════════════════════════════════════════
// BULK CSV ADMISSION (CI3 parity) — template / validate / import
// One row per learner; guardian matched by phone so siblings share
// the guardian record fields already on file. No external CSV deps.
// ═══════════════════════════════════════════════════════════════

const BULK_CSV_HEADERS = [
  "firstName", "lastName", "otherNames", "gender", "dateOfBirth",
  "guardianName", "guardianPhone", "guardianAltPhone", "guardianEmail",
  "guardianOccupation", "relationship", "hometown", "previousSchool",
];
const BULK_ROW_CAP = 500;

/** Whitespace-insensitive phone key (+233/0 formats compared as typed). */
const normPhone = (p: string) => p.replace(/\s+/g, "");

type CsvRecord = { line: number; cells: string[] };

/** Small RFC-4180-style CSV parser: quoted fields, "" escapes, commas and
 *  newlines inside quotes, CRLF/CR/LF line endings and a leading UTF-8 BOM.
 *  `line` is the 1-based physical line where each record starts. */
function parseCsv(text: string): CsvRecord[] {
  const src = text.replace(/^\uFEFF/, "");
  const records: CsvRecord[] = [];
  let cells: string[] = [];
  let cur = "";
  let inQuotes = false;
  let line = 1;
  let recordLine = -1; // -1 = no content seen for the current record yet
  const touch = () => { if (recordLine === -1) recordLine = line; };
  const endRecord = () => {
    if (recordLine !== -1) { cells.push(cur); records.push({ line: recordLine, cells }); }
    cells = []; cur = ""; recordLine = -1;
  };
  for (let i = 0; i < src.length; i++) {
    const ch = src[i];
    if (inQuotes) {
      if (ch === '"') {
        if (src[i + 1] === '"') { cur += '"'; i++; }
        else inQuotes = false;
      } else {
        if (ch === "\r") { if (src[i + 1] === "\n") i++; line++; }
        else if (ch === "\n") line++;
        else cur += ch;
      }
      touch();
      continue;
    }
    if (ch === '"' && cur === "") { inQuotes = true; touch(); continue; }
    if (ch === ",") { touch(); cells.push(cur); cur = ""; continue; }
    if (ch === "\n" || ch === "\r") {
      if (ch === "\r" && src[i + 1] === "\n") i++;
      endRecord(); line++;
      continue;
    }
    touch(); cur += ch;
  }
  endRecord();
  return records;
}

type BulkRowData = Record<string, string>; // keyed by lowercased header name
type ParsedBulkRow = { line: number; data: BulkRowData };

/** Header row → column map; skips blank rows and the template's example row. */
function mapCsvToRows(csv: string): { error: string; rows?: undefined } | { error?: undefined; rows: ParsedBulkRow[] } {
  const records = parseCsv(csv);
  if (!records.length) return { error: "The CSV file is empty — download the template, then fill in one row per learner." };
  const header = records[0].cells.map((h) => h.trim().toLowerCase());
  if (!header.includes("firstname")) {
    return { error: "The first line must be the template header row (firstName,lastName,…). Download the template and paste your rows underneath it." };
  }
  const rows: ParsedBulkRow[] = [];
  for (const rec of records.slice(1)) {
    const data: BulkRowData = {};
    rec.cells.forEach((cell, i) => { const key = header[i]; if (key) data[key] = cell.trim(); });
    if (Object.values(data).every((v) => !v)) continue; // blank line
    if ((data.firstname ?? "").toLowerCase() === "example") continue; // template example row
    rows.push({ line: rec.line, data });
  }
  return { rows };
}

type GuardianOnFile = {
  guardianName: string; guardianPhone: string; guardianAltPhone: string | null;
  guardianEmail: string | null; guardianOccupation: string | null; guardianAddress: string | null; relationship: string;
};

type BulkRowResult = {
  line: number; name: string; ok: boolean;
  errors: string[]; warnings: string[];
  guardianMatch?: { name: string; phone: string };
  // internal (stripped before responding):
  data?: BulkRowData; reuse?: GuardianOnFile; gender?: "M" | "F";
  dobDate?: Date | null; dobYear?: number;
};

const dupKey = (firstName: string, lastName: string, phone: string) =>
  `${firstName.trim().toLowerCase()}|${lastName.trim().toLowerCase()}|${normPhone(phone)}`;

/** Parse + validate every row against the file itself and the school register.
 *  Pure check — never writes anything. Results stay in row order. */
async function validateBulkRows(schoolId: string, rows: ParsedBulkRow[]) {
  // guardians-on-file = students with an enrollment (any status) in this school
  const schoolStudents = await db.student.findMany({
    where: { enrollments: { some: { schoolId } } },
    select: {
      firstName: true, lastName: true,
      guardianName: true, guardianPhone: true, guardianAltPhone: true,
      guardianEmail: true, guardianOccupation: true, guardianAddress: true, relationship: true,
    },
  });
  const guardianByPhone = new Map<string, GuardianOnFile>();
  const existingLearners = new Set<string>();
  for (const s of schoolStudents) {
    const key = normPhone(s.guardianPhone);
    if (key && !guardianByPhone.has(key)) guardianByPhone.set(key, s);
    existingLearners.add(dupKey(s.firstName, s.lastName, s.guardianPhone));
  }

  const seenInFile = new Map<string, number>();
  const results: BulkRowResult[] = [];

  for (const { line, data } of rows) {
    const errors: string[] = [];
    const warnings: string[] = [];
    const firstName = data.firstname ?? "";
    const lastName = data.lastname ?? "";
    const rawPhone = data.guardianphone ?? "";
    const phone = normPhone(rawPhone);
    const name = `${firstName} ${lastName}`.trim() || "(no name)";

    if (!firstName) errors.push("First name is required");
    if (!lastName) errors.push("Last name is required");

    // gender: M/F (male/female accepted case-insensitively, normalised)
    let gender: "M" | "F" | null = null;
    const g = (data.gender ?? "").trim().toLowerCase();
    if (!g) errors.push("Gender is required (M or F)");
    else if (g === "m" || g === "male") gender = "M";
    else if (g === "f" || g === "female") gender = "F";
    else errors.push(`Gender must be M or F (male/female accepted) — got "${data.gender}"`);

    // dateOfBirth: optional, but when present a real YYYY-MM-DD calendar date
    let dobDate: Date | null = null;
    let dobYear: number | undefined;
    const dob = data.dateofbirth ?? "";
    if (dob) {
      const m = dob.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      const d = m ? new Date(Date.UTC(+m[1], +m[2] - 1, +m[3])) : null;
      if (!d || d.getUTCFullYear() !== +m![1] || d.getUTCMonth() !== +m![2] - 1 || d.getUTCDate() !== +m![3]) {
        errors.push("Date of birth must be a valid date as YYYY-MM-DD (e.g. 2016-05-14)");
      } else if (d.getTime() > Date.now()) {
        errors.push("Date of birth cannot be in the future");
      } else {
        dobDate = d;
        dobYear = d.getUTCFullYear();
      }
    }

    // guardian phone: required; spaces stripped; +233/0 formats accepted as typed
    if (!phone) errors.push("Guardian phone is required (MoMo number)");
    else if (phone.replace(/\D/g, "").length < 9) errors.push("Guardian phone looks too short — use 0XXXXXXXXX or +233XXXXXXXXX");

    // duplicate rows INSIDE the file (same learner name + guardian phone)
    if (firstName && lastName && phone) {
      const key = dupKey(firstName, lastName, phone);
      const firstLine = seenInFile.get(key);
      if (firstLine !== undefined) errors.push(`Duplicate of line ${firstLine} in this file (same learner name + guardian phone)`);
      else seenInFile.set(key, line);
    }

    // existing guardian on file (phone match) → warning, details reused on import
    const reuse = phone ? guardianByPhone.get(phone) : undefined;
    if (reuse) {
      warnings.push(`existing guardian: ${reuse.guardianName} (${normPhone(reuse.guardianPhone)}) — their details will be reused`);
    } else if (phone && !(data.guardianname ?? "").trim()) {
      errors.push("Guardian name is required (no existing guardian with this phone to reuse)");
    }

    // learner already on the school roll (same name + guardian phone) → error
    if (firstName && lastName && phone && existingLearners.has(dupKey(firstName, lastName, phone))) {
      errors.push(`${firstName} ${lastName} is already enrolled in this school with guardian ${phone}`);
    }

    results.push({
      line, name, ok: errors.length === 0, errors, warnings,
      ...(reuse ? { guardianMatch: { name: reuse.guardianName, phone: normPhone(reuse.guardianPhone) } } : {}),
      data, reuse, gender: gender ?? undefined, dobDate, dobYear,
    });
  }

  const validCount = results.filter((r) => r.ok).length;
  return { results, validCount, invalidCount: results.length - validCount };
}

/** Strip internal fields before a row result leaves the server. */
const publicRow = (r: BulkRowResult) => ({
  line: r.line, name: r.name, ok: r.ok, errors: r.errors, warnings: r.warnings,
  ...(r.guardianMatch ? { guardianMatch: r.guardianMatch } : {}),
});

// ── GET /api/school/[id]/ops/admissions — admission workspace ──
// classrooms with capacity/fees, invoice discounts, recent admissions
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["admissions.manage"] });
  if (!auth.ok) return auth.res;

  // ── action=bulk-template — CSV template for one class (header + example row) ──
  if (new URL(req.url).searchParams.get("action") === "bulk-template") {
    const classroomId = new URL(req.url).searchParams.get("classroomId") ?? "";
    const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!classroom || classroom.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    const example = [
      "Example", "(delete this row)", "Ama Kesi", "Female", "2016-05-14",
      "Maame Mansa", "024 000 0000", "", "", "Trader", "Mother", "Kumasi", "Sunrise International School",
    ];
    const csv = [BULK_CSV_HEADERS.join(","), example.join(",")].join("\r\n") + "\r\n";
    const slug = classroom.name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "") || "class";
    return new NextResponse(csv, {
      headers: {
        "content-type": "text/csv; charset=utf-8",
        "content-disposition": `attachment; filename="nyansapo-admissions-${slug}.csv"`,
      },
    });
  }

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } });

  const classrooms = await db.classroom.findMany({
    where: { schoolId },
    include: {
      classTeacher: true,
      _count: { select: { enrollments: { where: { status: "ACTIVE" } } } },
      feeStructures: { where: { termId: term?.id }, include: { items: true }, orderBy: { id: "desc" }, take: 1 },
    },
    orderBy: [{ gradeLevel: "asc" }, { name: "asc" }],
  });

  const discounts = await db.discount.findMany({
    where: { schoolId, scope: "INVOICE", active: true },
    orderBy: { name: "asc" },
  });

  // boarding availability for optional at-intake allocation (iEduPro parity):
  // houses → dorms → currently-AVAILABLE beds only
  const houses = await db.boardingHouse.findMany({
    where: { schoolId, status: { not: "MAINTENANCE" } },
    include: {
      dormitories: {
        where: { status: { not: "MAINTENANCE" } },
        include: { beds: { where: { status: "AVAILABLE" }, orderBy: { code: "asc" } } },
        orderBy: { name: "asc" },
      },
    },
    orderBy: { name: "asc" },
  });

  const recent = await db.enrollment.findMany({
    where: { schoolId, admissionDate: { gte: new Date(Date.now() - 120 * 864e5) } },
    include: { student: true, classroom: true },
    orderBy: { admissionDate: "desc" },
    take: 12,
  });

  const studentsTotal = await db.student.count();

  return NextResponse.json({
    term: term ? { id: term.id, name: term.name, year: term.academicYear.name } : null,
    classrooms: classrooms.map((c) => ({
      id: c.id,
      name: c.name,
      gradeLevel: c.gradeLevel,
      stageLabel: c.stageLabel,
      capacity: c.capacity,
      classTeacher: c.classTeacher?.fullName ?? null,
      enrolled: c._count.enrollments,
      feeItems: c.feeStructures[0]?.items.map((i) => ({ name: i.name, amountCents: i.amountCents, isOptional: i.isOptional })) ?? [],
      feeTotal: c.feeStructures[0]?.totalCents ?? 0,
    })),
    discounts: discounts.map((d) => ({ id: d.id, name: d.name, type: d.type, value: d.value, notes: d.notes })),
    boarding: houses
      .map((h) => ({
        id: h.id, name: h.name,
        dorms: h.dormitories
          .map((d) => ({ id: d.id, name: d.name, type: d.type, beds: d.beds.map((b) => ({ id: b.id, code: b.code, bedType: b.bedType })) }))
          .filter((d) => d.beds.length > 0),
      }))
      .filter((h) => h.dorms.length > 0),
    recent: recent.map((e) => ({
      id: e.id,
      student: `${e.student.firstName} ${e.student.lastName}`,
      nyansapoIndex: e.student.nyansapoIndex,
      classroom: e.classroom.name,
      guardianPhone: e.student.guardianPhone,
      admittedAt: e.admissionDate,
      status: e.status,
    })),
    indexSeq: studentsTotal + 1,
  });
}

// ── POST /api/school/[id]/ops/admissions ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["admissions.manage"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  if (action === "admit") {
    const {
      firstName, lastName, otherNames, gender, dateOfBirth,
      classroomId, guardianName, guardianPhone, guardianAltPhone, guardianEmail, guardianOccupation, guardianAddress,
      relationship, hometown, previousSchool,
      fatherName, fatherPhone, fatherOccupation, motherName, motherPhone, motherOccupation,
      nationality, religion, bloodGroup, allergies, medicalConditions, emergencyContact, dietNote, photoUrl,
      bedId,
      billItems, discountId, initialPaymentCents, initialPaymentMethod,
    } = body;

    // ── validation ──
    if (!firstName?.trim() || !lastName?.trim()) return NextResponse.json({ error: "First and last name are required" }, { status: 400 });
    if (!gender) return NextResponse.json({ error: "Gender is required" }, { status: 400 });
    if (!classroomId) return NextResponse.json({ error: "Choose a class to enrol in" }, { status: 400 });
    if (!guardianName?.trim() || !guardianPhone?.trim()) return NextResponse.json({ error: "Guardian name and phone are required" }, { status: 400 });
    const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!classroom || classroom.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });

    const items: { name: string; amountCents: number }[] = (billItems ?? [])
      .filter((i: any) => i.name?.trim() && Number(i.amountCents) > 0)
      .map((i: any) => ({ name: String(i.name).trim(), amountCents: Math.round(Number(i.amountCents)) }));
    const gross = items.reduce((s, i) => s + i.amountCents, 0);

    // ── optional boarding bed: fail fast if it slipped away ──
    let bedTarget: { id: string; code: string; dormName: string; houseName: string } | null = null;
    if (bedId) {
      const b = await db.bed.findUnique({ where: { id: bedId }, include: { dormitory: { include: { house: true } } } });
      if (!b || b.schoolId !== schoolId) return NextResponse.json({ error: "Bed not found" }, { status: 404 });
      if (b.status !== "AVAILABLE" || b.studentId) return NextResponse.json({ error: `Bed ${b.code} was just taken — pick another` }, { status: 409 });
      bedTarget = { id: b.id, code: b.code, dormName: b.dormitory.name, houseName: b.dormitory.house.name };
    }
    // portrait arrives as a client-resized data-URL — validate + store as a
    // binary MediaAsset; the student row keeps only the short /api/media URL
    // (junk input degrades to no photo, as before)
    const photo = typeof photoUrl === "string" && photoUrl.startsWith("data:image/") && photoUrl.length < 700_000
      ? await storeMedia({ db, schoolId, kind: "PORTRAIT", dataUrl: photoUrl })
      : null;

    // ── student + national index (sync-safe atomic counter) ──
    const nyansapoIndex = await nextStudentIndex(new Date().getFullYear());

    const student = await db.student.create({
      data: {
        nyansapoIndex,
        firstName: String(firstName).trim(),
        lastName: String(lastName).trim(),
        otherNames: otherNames?.trim() || null,
        gender: gender === "F" ? "F" : "M",
        dateOfBirth: dateOfBirth ? new Date(dateOfBirth) : null,
        guardianName: String(guardianName).trim(),
        guardianPhone: String(guardianPhone).trim(),
        guardianAltPhone: guardianAltPhone?.trim() || null,
        guardianEmail: guardianEmail?.trim() || null,
        guardianOccupation: guardianOccupation?.trim() || null,
        guardianAddress: guardianAddress?.trim() || null,
        relationship: relationship || "Parent",
        hometown: hometown?.trim() || null,
        previousSchool: previousSchool?.trim() || null,
        fatherName: fatherName?.trim() || null,
        fatherPhone: fatherPhone?.trim() || null,
        fatherOccupation: fatherOccupation?.trim() || null,
        motherName: motherName?.trim() || null,
        motherPhone: motherPhone?.trim() || null,
        motherOccupation: motherOccupation?.trim() || null,
        nationality: nationality?.trim() || null,
        religion: religion?.trim() || null,
        bloodGroup: bloodGroup || null,
        allergies: allergies?.trim() || null,
        medicalConditions: medicalConditions?.trim() || null,
        emergencyContact: emergencyContact?.trim() || null,
        dietNote: dietNote?.trim() || null,
        photoUrl: photo,
        photoSeed: photo ? null : `${firstName}${lastName}`.toLowerCase(),
        status: "ACTIVE",
      },
    });

    // ── boarding allocation (optional) ──
    let bed: { id: string; code: string; dorm: string; house: string } | null = null;
    if (bedTarget) {
      await db.bed.update({ where: { id: bedTarget.id }, data: { studentId: student.id, status: "ASSIGNED", assignedAt: new Date() } });
      bed = { id: bedTarget.id, code: bedTarget.code, dorm: bedTarget.dormName, house: bedTarget.houseName };
    }

    // ── enrollment with roll number ──
    const rollCount = await db.enrollment.count({ where: { classroomId, status: "ACTIVE" } });
    const enrollment = await db.enrollment.create({
      data: {
        studentId: student.id, classroomId, schoolId,
        rollNo: rollCount + 1, admissionDate: new Date(), status: "ACTIVE",
      },
    });

    // ── invoice (term bill) ──
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    let invoice: any = null;
    let discountCents = 0;
    let discountName: string | null = null;
    if (items.length && term) {
      let discount: any = null;
      if (discountId) {
        discount = await db.discount.findUnique({ where: { id: discountId } });
        if (!discount || discount.schoolId !== schoolId || discount.scope !== "INVOICE") discount = null;
      }
      if (discount) {
        discountCents = discount.type === "PERCENT" ? Math.round((gross * discount.value) / 100) : Math.min(discount.value, gross);
        discountName = discount.name;
        // record the application for the audit trail
        await db.discountApplication.create({
          data: { discountId: discount.id, studentId: student.id, schoolId, reason: `Admission bill — ${discount.name}`, appliedByName: user.fullName },
        });
      }
      invoice = await db.invoice.create({
        data: {
          invoiceNo: await nextInvoiceNo(),
          studentId: student.id, schoolId, termId: term.id, classroomName: classroom.name,
          totalCents: gross, discountCents, discountReason: discountName, paidCents: 0, status: "UNPAID",
          dueDate: new Date(Date.now() + 14 * 864e5),
        },
      });
      for (const it of items) await db.invoiceItem.create({ data: { invoiceId: invoice.id, name: it.name, amountCents: it.amountCents } });
    }

    // ── optional first payment at the counter ──
    let receipt: any = null;
    const pay = Math.round(Number(initialPaymentCents) || 0);
    if (pay > 0 && invoice) {
      const payable = invoice.totalCents - discountCents;
      const amt = Math.min(pay, payable);
      const payment = await db.payment.create({
        data: {
          receiptNo: await nextReceiptNo(),
          invoiceId: invoice.id, studentId: student.id, schoolId, amountCents: amt,
          method: initialPaymentMethod ?? "CASH", receivedByName: user.fullName, paidAt: new Date(),
        },
      });
      await db.invoice.update({
        where: { id: invoice.id },
        data: { paidCents: amt, status: amt >= payable ? "PAID" : "PARTIAL" },
      });
      receipt = await invoicePaymentReceipt(db, payment.id);
    }

    // ── welcome SMS (failures never block admission) ──
    try {
      await sendSms({
        db, schoolId, studentId: student.id, recipientName: guardianName, recipientPhone: student.guardianPhone,
        body: `Akwaaba! ${firstName} has been admitted to ${classroom.name}. Nyansapo Index: ${nyansapoIndex}. ${invoice ? `Term bill: GH₵${((gross - discountCents) / 100).toFixed(2)}.` : ""}`,
        category: "ADMISSION", sentByName: user.fullName,
      });
    } catch { /* ignore SMS errors */ }

    return NextResponse.json({
      student: { id: student.id, name: `${student.firstName} ${student.lastName}`, nyansapoIndex },
      enrollment: { id: enrollment.id, rollNo: enrollment.rollNo, classroom: classroom.name },
      bed,
      invoice: invoice ? { id: invoice.id, invoiceNo: invoice.invoiceNo, total: gross, discount: discountCents, payable: gross - discountCents } : null,
      receipt,
    });
  }

  // ── action=bulk-validate — parse + check a CSV without creating anything ──
  if (action === "bulk-validate") {
    const { classroomId, csv } = body;
    if (typeof csv !== "string" || !csv.trim()) return NextResponse.json({ error: "Upload a CSV file first" }, { status: 400 });
    if (!classroomId) return NextResponse.json({ error: "Choose the class to enrol the learners in" }, { status: 400 });
    const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!classroom || classroom.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });

    const parsed = mapCsvToRows(csv);
    if (!parsed.rows) return NextResponse.json({ error: parsed.error ?? "Could not read the CSV file" }, { status: 400 });
    if (parsed.rows.length === 0) return NextResponse.json({ error: "No learner rows found — fill in one row per learner under the header row." }, { status: 400 });
    if (parsed.rows.length > BULK_ROW_CAP) return NextResponse.json({ error: `Too many rows — bulk admission accepts at most ${BULK_ROW_CAP} learners per file. Split the file and import in batches.` }, { status: 400 });

    const { results, validCount, invalidCount } = await validateBulkRows(schoolId, parsed.rows);
    return NextResponse.json({ rows: results.map(publicRow), validCount, invalidCount, classroom: classroom.name });
  }

  // ── action=bulk-import — admit every valid row (all-or-nothing on errors,
  //    explicit confirmation required when guardian details will be reused) ──
  if (action === "bulk-import") {
    const { classroomId, csv, confirmGuardianReuse } = body;
    if (typeof csv !== "string" || !csv.trim()) return NextResponse.json({ error: "Upload a CSV file first" }, { status: 400 });
    if (!classroomId) return NextResponse.json({ error: "Choose the class to enrol the learners in" }, { status: 400 });
    const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!classroom || classroom.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });

    const parsed = mapCsvToRows(csv);
    if (!parsed.rows) return NextResponse.json({ error: parsed.error ?? "Could not read the CSV file" }, { status: 400 });
    if (parsed.rows.length === 0) return NextResponse.json({ error: "No learner rows found — fill in one row per learner under the header row." }, { status: 400 });
    if (parsed.rows.length > BULK_ROW_CAP) return NextResponse.json({ error: `Too many rows — bulk admission accepts at most ${BULK_ROW_CAP} learners per file. Split the file and import in batches.` }, { status: 400 });
    const rowCount = parsed.rows.length;

    // re-validate: any invalid row blocks the whole import
    const { results, validCount, invalidCount } = await validateBulkRows(schoolId, parsed.rows);
    if (invalidCount > 0) {
      return NextResponse.json({
        error: `Import blocked — ${invalidCount} of ${rowCount} row${invalidCount === 1 ? "" : "s"} ha${invalidCount === 1 ? "s" : "ve"} errors. Fix the file and upload again.`,
        rows: results.map(publicRow), validCount, invalidCount,
      }, { status: 400 });
    }

    // guardian reuse needs an explicit confirmation (UI re-submits after the dialog)
    const matches = results.filter((r) => r.guardianMatch);
    if (matches.length > 0 && confirmGuardianReuse !== true) {
      return NextResponse.json({
        error: `${matches.length} row${matches.length === 1 ? "" : "s"} reuse a guardian already on file — confirm to continue.`,
        requireGuardianConfirm: true,
        matches: matches.map((m) => ({ line: m.line, name: m.name, guardianMatch: m.guardianMatch })),
        rows: results.map(publicRow), validCount, invalidCount,
      }, { status: 409 });
    }

    // import loop — per-row results; one bad row never rolls back the others
    const rollBase = await db.enrollment.count({ where: { classroomId, status: "ACTIVE" } });
    let nextRoll = rollBase + 1;
    let created = 0;
    const indexes: { line: number; name: string; studentId: string; index: string; rollNo: number }[] = [];
    const failed: { line: number; name: string; error: string }[] = [];

    for (let i = 0; i < rowCount; i++) {
      const row = parsed.rows[i];
      const v = results[i]; // results are row-parallel
      const data = v.data!;
      try {
        // confirmed reuse: sibling inherits the guardian record already on file
        // (contact fields shared; relationship stays the row's own, if given)
        const reuse = v.reuse;
        const guardian = reuse
          ? {
              guardianName: reuse.guardianName,
              guardianPhone: reuse.guardianPhone,
              guardianAltPhone: reuse.guardianAltPhone,
              guardianEmail: reuse.guardianEmail,
              guardianOccupation: reuse.guardianOccupation,
              guardianAddress: reuse.guardianAddress,
            }
          : {
              guardianName: data.guardianname ?? "",
              guardianPhone: normPhone(data.guardianphone ?? ""),
              guardianAltPhone: data.guardianaltphone || null,
              guardianEmail: data.guardianemail || null,
              guardianOccupation: data.guardianoccupation || null,
              guardianAddress: null, // not a template column — captured later from the guardian record
            };
        const relationship = data.relationship?.trim() || reuse?.relationship || "Parent";

        const nyansapoIndex = await nextStudentIndex(v.dobYear ?? new Date().getFullYear());
        const student = await db.student.create({
          data: {
            nyansapoIndex,
            firstName: data.firstname ?? "",
            lastName: data.lastname ?? "",
            otherNames: data.othernames || null,
            gender: v.gender ?? "M",
            dateOfBirth: v.dobDate ?? null,
            guardianName: guardian.guardianName,
            guardianPhone: guardian.guardianPhone,
            guardianAltPhone: guardian.guardianAltPhone,
            guardianEmail: guardian.guardianEmail,
            guardianOccupation: guardian.guardianOccupation,
            guardianAddress: guardian.guardianAddress,
            relationship,
            hometown: data.hometown || null,
            previousSchool: data.previousschool || null,
            status: "ACTIVE",
          },
        });

        try {
          const rollNo = nextRoll++;
          const enrollment = await db.enrollment.create({
            data: {
              studentId: student.id, classroomId, schoolId,
              rollNo, admissionDate: new Date(), status: "ACTIVE",
            },
          });
          created++;
          indexes.push({ line: row.line, name: `${student.firstName} ${student.lastName}`, studentId: student.id, index: nyansapoIndex, rollNo });
        } catch (e) {
          // enrollment failed — remove the orphan learner so the file can be retried cleanly
          await db.student.delete({ where: { id: student.id } }).catch(() => {});
          throw e;
        }
      } catch (e: any) {
        failed.push({ line: row.line, name: v.name, error: e?.meta?.target?.includes?.("nyansapoIndex") ? "Nyansapo Index collision — retry this row" : (e?.message ?? "Could not admit this learner") });
      }
    }

    return NextResponse.json({ created, failed, indexes, classroom: classroom.name });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}
