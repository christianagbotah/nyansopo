# iEduPro (Lightworld) — UX findings for Nyansapo parity
Explored live as admin on 2026-10-31.

## 1. Daily Fees
- Fee types: Feeding (lunch), Breakfast (optional), Classes (course fee), Water (weekly), Transport Fare
- Per-class-section rates (Feeding GHS 12, Breakfast 2-3, Classes 2, Water 5)
- Fee modules enable/disable per school
- Collection modes: Classroom Only / Cashier Only / Hybrid
- Teacher fee-collection permissions: All / None / Selective (per class, per fee type)
- Collection UX: search student → fee item cards with badges (Daily/Weekly, Required/Optional),
  Collect (F/B/C/W/T) toggles, Payment Date (backdate), Method (Cash/MoMo/Cheque/Bank Transfer),
  Amount auto-sum, Tendered, Change, Mark Present, Hold, Receipt
- Wallets: Prepaid Balance, Arrears Balance, Transport Wallet (+Top Up)
- Dashboard: Collected by date/term, Outstanding, Advance (Payables) — per fee type
- Transport usage: Not Using / Morning Only / Afternoon Only / Both Ways / Already Boarded

## 2. Transport
- Stats: students on transport, active routes, fare collected/term
- Routes table: Id | Route Name | Vehicles (plate) | Fare | Description | Students | Actions
- Add Route: name, vehicle reg number, fare (GHC), description
- Assign Students: route select → student table w/ checkboxes → Assign Selected / Unassign
- Real names: Katamanso, Castle Gate, Appolonia, Zenu, Amrahia, Legon Hills... fares GHS 3.5-6
- Plates: GT-4745-23, GS-238-14, GW-2749-12

## 3. SMS
- Automation cards: name, Active/Inactive, trigger, audience, sent count, message with
  {student_name} {class} {date} {amount} {attendance_status}; Test/Logs/Edit/Activate
- 9 templates: Student Present, Welcome New Student, Fee Payment Confirmation, Fee Overdue
  Reminder, Exam Results Published, Absence Alert, Birthday Wishes, Term Begins, Term Ends
- SMS Log: Date | Student | Phone | Type | Message | Status
- Bill Reminders: preview + Print / Test SMS / Send SMS
- Settings: Active Service (gateway), Sender Name (≤11 chars), Client ID, Client Secret

## 4. Payroll (Ghana compliant)
- Staff picker grouped: Administrators / Teachers (IDs ADM-n, KISPECSN-nn)
- Earnings: Basic, Market Premium, Teaching Allowance, Responsibility Allowance,
  Extra Classes, Rural Allowance, Other Allowance
- Employer: SSNIT Tier 1 13.5% (not deducted)
- Employee deductions: SSNIT Tier 2 5% (+ Tier 2 Provider e.g. Petra Trust),
  PAYE auto-calc progressive brackets, GETFund 2.5%, NHIL 2.5%, Salary Advance, Loan,
  Welfare, GNAT Dues, Other
- Attendance: Working Days / Present / Absent
- Summary: Basic, Allowances, Gross, Statutory, Other, Net; Calculate / Auto-calc / Process
- Payslips list: reference, ID No, Name, Account #, month, year, Status, salary cols, Pay Salary
- STATUTORY_RATES from DB: tier1_employer 13.5, tier1_employee 5.5, tier2 5, getfund 2.5, nhil 2.5

## 5. Invoicing (Student Billing)
- Bill items builder: Title, Category (General Bill / New Admission-Day / New Admission-Boarding /
  Day Only / Boarding Only / Arrears / New Admission-General), All-Classes level or Specific
  Classes multi-select, Amount, Description → Save All
- CREATE INVOICES (mass), CLONE INVOICES, LOAD INVOICES, PRINT BULK BILLS, RECENT RECEIPTS
- Manage: Bulk Approve/Reject, Bulk Modify, Export, Print, Email, SMS
- Example items: "SCHOOL FEES FOR CRECHE 1" GHS 750 (tuition/FM/toiletries/exams), "UTILITIES & HALL FEE"

## 6. Discounts
- Profiles: Name | Category (bill item or fee type e.g. Feeding) | Scope (Invoice / Daily Fees) |
  Method (Percentage / Fixed) | Value | Status
- Apply: Year + Term + Student (+ Invoice Code) + Profile → Assign
- Assignments + Reports + Approvals workflow
- Examples: PTA/teacher tuition discounts GHS 550-700 fixed; lunch discount 100%

## 7. Inventory
- Dashboard: Total Products, Low Stock Alerts, Stock Value, Sales 30d, Returns 30d, Refunded,
  Pending Orders + Value; Low Stock list; Recent Movements
- Product: Name*, SKU, Category (Art/Electronics/Food/Sports/Stationery), Cost*, Selling*, Qty*,
  Reorder Level, Description; export Excel/PDF/Print
- POS: product search, customer (Walk-in / Individual Students / By Class / By Residential Type),
  Cart, Order Summary, Complete Sale, Clear Cart, Recent Transactions
- Products sample: Exercise Book 40p GHS 3.50, Basketball GHS 55, Football GHS 50, Drawing Set GHS 25

## 8. Student Ledger
- Date | Student | Description | Invoice Code | Debit | Credit | Balance; filter year/term

## Design tokens
- Sidebar gradient: linear-gradient(135deg, #134E5E → #71B280); grouped menu headers
- Currency GHC/GH₵; DataTables w/ search+export; stat cards w/ icon; modal quick-forms
