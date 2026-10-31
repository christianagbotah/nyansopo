"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Package, Plus, TrendingDown, TrendingUp, Warehouse, Truck, ArrowDownRight, ArrowUpRight, RotateCcw, Search, Pencil, PackageOpen, Banknote, ShoppingBasket, ReceiptText, Minus, CheckCircle2 } from "lucide-react";

type Item = { id: string; name: string; sku: string | null; category: string; unit: string; unitCost: number; sellPrice: number | null; stockQty: number; reorderLevel: number; location: string | null };
type Movement = { id: string; item: string; itemId: string; type: string; qty: number; unitCost: number | null; partyType: string | null; partyName: string | null; student: string | null; chargedToInvoice: boolean; note: string | null; createdAt: string };
type Supplier = { id: string; name: string; phone: string | null; email: string | null; itemsSupplied: string | null; notes: string | null };
type Sale = { saleNo: string; buyer: string; method: string; total: number; at: string; lines: { item: string; qty: number; amount: number }[] };
type InventoryData = {
  items: Item[];
  movements: Movement[];
  suppliers: Supplier[];
  stats: { stockValue: number; lowStock: number; items: number; movements: number; salesMonth: number; salesCount: number };
  lowStockItems: { id: string; name: string; stockQty: number; reorderLevel: number; unit: string }[];
  students: { id: string; name: string; classroom: string }[];
  sales: Sale[];
};

const CATS = ["BOOKS", "UNIFORMS", "STATIONERY", "FOOD", "CLEANING", "FUEL", "OTHER"];
const catLabel = (c: string) => c.charAt(0) + c.slice(1).toLowerCase();

export function Inventory() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<InventoryData | null>(null);
  const [tab, setTab] = useState("items");
  const [itemDialog, setItemDialog] = useState<Item | "new" | null>(null);
  const [mvFor, setMvFor] = useState<{ item: Item; type: string } | null>(null);
  const [supDialog, setSupDialog] = useState<Supplier | "new" | null>(null);
  const [catFilter, setCatFilter] = useState("all");
  const [search, setSearch] = useState("");
  // POS state
  const [cart, setCart] = useState<Record<string, number>>({});
  const [posSearch, setPosSearch] = useState("");
  const [buyerMode, setBuyerMode] = useState("walkin");
  const [buyerName, setBuyerName] = useState("");
  const [buyerStudentId, setBuyerStudentId] = useState("");
  const [payMethod, setPayMethod] = useState("CASH");
  const [saleBusy, setSaleBusy] = useState(false);
  const [saleReceipt, setSaleReceipt] = useState<Sale | null>(null);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<InventoryData>(`/api/school/${currentSchoolId}/ops/inventory`).then(setData).catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  const items = useMemo(() => {
    const q = search.toLowerCase();
    return (data?.items ?? []).filter((i) => (catFilter === "all" || i.category === catFilter) && (!q || i.name.toLowerCase().includes(q) || (i.sku ?? "").toLowerCase().includes(q)));
  }, [data, catFilter, search]);

  const sellable = useMemo(() => {
    const q = posSearch.toLowerCase();
    return (data?.items ?? []).filter((i) => i.sellPrice !== null && (!q || i.name.toLowerCase().includes(q) || (i.sku ?? "").toLowerCase().includes(q)));
  }, [data, posSearch]);

  const cartLines = useMemo(() => Object.entries(cart).map(([id, qty]) => {
    const it = data?.items.find((x) => x.id === id);
    return it ? { item: it, qty } : null;
  }).filter(Boolean) as { item: Item; qty: number }[], [cart, data]);
  const cartTotal = cartLines.reduce((s, l) => s + (l.item.sellPrice ?? 0) * l.qty, 0);

  function addToCart(it: Item) {
    setCart((c) => {
      const next = (c[it.id] ?? 0) + 1;
      if (next > it.stockQty) { toast({ title: "Not enough stock", description: `Only ${it.stockQty} ${it.unit} of ${it.name} available.` }); return c; }
      return { ...c, [it.id]: next };
    });
  }
  function setCartQty(it: Item, qty: number) {
    setCart((c) => {
      if (qty <= 0) { const { [it.id]: _drop, ...rest } = c; return rest; }
      if (qty > it.stockQty) { toast({ title: "Not enough stock", description: `Only ${it.stockQty} ${it.unit} available.` }); return c; }
      return { ...c, [it.id]: qty };
    });
  }

  async function completeSale() {
    if (!currentSchoolId || cartLines.length === 0) return;
    if (buyerMode === "student" && !buyerStudentId) { toast({ title: "Choose the learner buying" }); return; }
    setSaleBusy(true);
    try {
      const res = await apiPost<{ sale: Sale }>(`/api/school/${currentSchoolId}/ops/inventory`, {
        action: "sale",
        lines: cartLines.map((l) => ({ itemId: l.item.id, qty: l.qty })),
        buyerName: buyerMode === "walkin" ? buyerName : undefined,
        studentId: buyerMode === "student" ? buyerStudentId : undefined,
        method: payMethod,
      });
      setSaleReceipt(res.sale);
      setCart({}); setBuyerName(""); setBuyerStudentId("");
      load();
    } catch (e: any) { toast({ title: "Sale failed", description: e.message, variant: "destructive" }); } finally { setSaleBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading inventory…" />;

  return (
    <div>
      <ModuleHeader
        title="Inventory & Stores"
        description="Uniforms, exercise books, textbooks, kitchen supplies — track stock, reorder levels, purchases and issues to learners (chargeable straight to their invoices)."
        actions={
          <div className="flex gap-2 flex-wrap">
            <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setSupDialog("new")}><Truck className="size-4" /> Supplier</Button>
            <Button size="sm" className="gap-1.5" onClick={() => setItemDialog("new")}><Plus className="size-4" /> New item</Button>
          </div>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Warehouse className="size-4" />} label="Stock value" value={fmtCedi(data.stats.stockValue, { compact: true })} tone="gold" sub={`${data.stats.items} items`} />
        <StatCard icon={<PackageOpen className="size-4" />} label="Low stock" value={data.stats.lowStock} tone={data.stats.lowStock ? "danger" : "default"} sub="below reorder level" />
        <StatCard icon={<Banknote className="size-4" />} label="Sales this month" value={fmtCedi(data.stats.salesMonth, { compact: true })} tone="primary" sub={`${data.sales.length} recent sales`} />
        <StatCard icon={<ArrowDownRight className="size-4" />} label="Issues (recent)" value={data.movements.filter((m) => m.type === "ISSUE").length} sub="sales, learners, staff & classes" />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList>
          <TabsTrigger value="pos">Counter sales</TabsTrigger>
          <TabsTrigger value="items">Stock ({items.length})</TabsTrigger>
          <TabsTrigger value="low">Low stock ({data.lowStockItems.length})</TabsTrigger>
          <TabsTrigger value="movements">Movements</TabsTrigger>
          <TabsTrigger value="suppliers">Suppliers ({data.suppliers.length})</TabsTrigger>
        </TabsList>

        {/* ── COUNTER SALES (POS) ── */}
        <TabsContent value="pos">
          <div className="grid lg:grid-cols-3 gap-4">
            {/* product picker */}
            <Card className="lg:col-span-2">
              <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base">Pick items — tap to add</CardTitle>
                <div className="relative">
                  <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
                  <Input placeholder="Search item or SKU…" value={posSearch} onChange={(e) => setPosSearch(e.target.value)} className="pl-8 w-52 h-8" />
                </div>
              </CardHeader>
              <CardContent className="max-h-[520px] overflow-y-auto nice-scroll">
                <div className="grid sm:grid-cols-2 gap-2">
                  {sellable.length === 0 && <div className="col-span-2"><EmptyState icon={<Package className="size-5" />} title="No sellable items" hint="Set a selling price on an item first — internal-only items can't be sold." /></div>}
                  {sellable.map((i) => {
                    const inCart = cart[i.id] ?? 0;
                    const out = i.stockQty - inCart <= 0;
                    return (
                      <button key={i.id} onClick={() => addToCart(i)} disabled={out}
                        className={`text-left rounded-lg border p-3 transition-colors ${out ? "opacity-50 cursor-not-allowed" : "hover:border-primary/50 hover:bg-primary/5"} ${inCart ? "border-primary/60 bg-primary/5" : ""}`}>
                        <div className="flex items-center justify-between gap-2">
                          <span className="font-medium text-sm truncate">{i.name}</span>
                          {inCart > 0 && <Badge className="text-[10px] shrink-0">×{inCart}</Badge>}
                        </div>
                        <div className="flex items-center justify-between mt-1">
                          <span className="text-sm font-semibold text-primary tabular-nums">{fmtCedi(i.sellPrice ?? 0)}</span>
                          <span className={`text-[11px] tabular-nums ${i.stockQty < i.reorderLevel ? "text-destructive" : "text-muted-foreground"}`}>{i.stockQty} {i.unit}</span>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </CardContent>
            </Card>

            {/* cart + checkout */}
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                  <CardTitle className="text-base">Cart</CardTitle>
                  {cartLines.length > 0 && <Button size="sm" variant="ghost" className="h-7 text-xs" onClick={() => setCart({})}>Clear</Button>}
                </CardHeader>
                <CardContent className="space-y-3">
                  {cartLines.length === 0 && <EmptyState icon={<ShoppingBasket className="size-5" />} title="Cart is empty" hint="Tap items on the left to add them." />}
                  {cartLines.map(({ item, qty }) => (
                    <div key={item.id} className="flex items-center justify-between gap-2 border-b last:border-0 pb-2 last:pb-0">
                      <div className="min-w-0">
                        <div className="text-sm font-medium truncate">{item.name}</div>
                        <div className="text-[11px] text-muted-foreground tabular-nums">{fmtCedi(item.sellPrice ?? 0)} × {qty} = <b className="text-foreground">{fmtCedi((item.sellPrice ?? 0) * qty)}</b></div>
                      </div>
                      <div className="flex items-center gap-1 shrink-0">
                        <Button size="icon" variant="outline" className="size-6" onClick={() => setCartQty(item, qty - 1)}><Minus className="size-3" /></Button>
                        <span className="w-6 text-center text-sm tabular-nums">{qty}</span>
                        <Button size="icon" variant="outline" className="size-6" onClick={() => setCartQty(item, qty + 1)}><Plus className="size-3" /></Button>
                      </div>
                    </div>
                  ))}
                  {cartLines.length > 0 && (
                    <div className="flex justify-between text-sm font-semibold pt-1 border-t">
                      <span>Total</span><span className="tabular-nums text-primary text-base">{fmtCedi(cartTotal)}</span>
                    </div>
                  )}
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-3"><CardTitle className="text-base">Checkout</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid grid-cols-2 gap-2">
                    <Button size="sm" variant={buyerMode === "walkin" ? "default" : "outline"} className="h-8" onClick={() => setBuyerMode("walkin")}>Walk-in</Button>
                    <Button size="sm" variant={buyerMode === "student" ? "default" : "outline"} className="h-8" onClick={() => setBuyerMode("student")}>Learner</Button>
                  </div>
                  {buyerMode === "walkin" ? (
                    <div className="space-y-1.5">
                      <Label>Buyer name (optional)</Label>
                      <Input placeholder="e.g. Auntie Adjoa" value={buyerName} onChange={(e) => setBuyerName(e.target.value)} className="h-9" />
                    </div>
                  ) : (
                    <div className="space-y-1.5">
                      <Label>Learner *</Label>
                      <Select value={buyerStudentId} onValueChange={setBuyerStudentId}>
                        <SelectTrigger className="h-9"><SelectValue placeholder="Choose learner…" /></SelectTrigger>
                        <SelectContent>
                          {(data.students ?? []).slice(0, 200).map((s) => <SelectItem key={s.id} value={s.id}>{s.name} · {s.classroom}</SelectItem>)}
                        </SelectContent>
                      </Select>
                    </div>
                  )}
                  <div className="space-y-1.5">
                    <Label>Payment method</Label>
                    <Select value={payMethod} onValueChange={setPayMethod}>
                      <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="CASH">Cash</SelectItem>
                        <SelectItem value="MOMO_MTN">MoMo — MTN</SelectItem>
                        <SelectItem value="MOMO_TELECEL">MoMo — Telecel</SelectItem>
                        <SelectItem value="MOMO_AT">MoMo — AirtelTigo</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <Button className="w-full gap-1.5" disabled={saleBusy || cartLines.length === 0} onClick={completeSale}>
                    <Banknote className="size-4" /> {saleBusy ? "Completing…" : `Complete sale · ${fmtCedi(cartTotal)}`}
                  </Button>
                </CardContent>
              </Card>
            </div>
          </div>

          {/* recent sales */}
          <Card className="mt-4">
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ReceiptText className="size-4 text-primary" /> Recent counter sales</CardTitle></CardHeader>
            <CardContent className="p-0">
              <div className="max-h-[320px] overflow-y-auto nice-scroll">
                {data.sales.length === 0 && <EmptyState title="No sales yet" hint="Complete your first counter sale above." />}
                {data.sales.map((s) => (
                  <div key={s.saleNo} className="px-4 py-2.5 border-b last:border-0 hover:bg-secondary/30 flex items-center justify-between gap-3 flex-wrap">
                    <div className="min-w-0">
                      <div className="text-sm font-medium">{s.buyer} <span className="text-muted-foreground font-mono text-xs">{s.saleNo}</span></div>
                      <div className="text-[11px] text-muted-foreground">{s.lines.map((l) => `${l.item}×${l.qty}`).join(" · ")}</div>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="text-sm font-semibold tabular-nums">{fmtCedi(s.total)}</div>
                      <div className="text-[11px] text-muted-foreground">{PAYMENT_LABELS[s.method] ?? s.method} · {fmtDate(s.at)}</div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── ITEMS ── */}
        <TabsContent value="items">
          <Card>
            <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 gap-2 flex-wrap">
              <div className="relative">
                <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
                <Input placeholder="Search item or SKU…" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-8 w-56 h-8" />
              </div>
              <Select value={catFilter} onValueChange={setCatFilter}>
                <SelectTrigger className="w-40 h-8"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All categories</SelectItem>
                  {CATS.map((c) => <SelectItem key={c} value={c}>{catLabel(c)}</SelectItem>)}
                </SelectContent>
              </Select>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[560px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Item</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Category</th>
                      <th className="px-3 py-2.5 font-medium text-right">In stock</th>
                      <th className="px-3 py-2.5 font-medium text-right hidden sm:table-cell">Reorder at</th>
                      <th className="px-3 py-2.5 font-medium text-right hidden lg:table-cell">Unit cost</th>
                      <th className="px-3 py-2.5 font-medium text-right hidden lg:table-cell">Sells at</th>
                      <th className="px-3 py-2.5 text-right"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {items.length === 0 && <tr><td colSpan={7}><EmptyState icon={<Package className="size-5" />} title="No items match" /></td></tr>}
                    {items.map((i) => {
                      const low = i.stockQty < i.reorderLevel;
                      return (
                        <tr key={i.id} className="border-b last:border-0 hover:bg-secondary/30">
                          <td className="px-3 py-2.5">
                            <div className="font-medium">{i.name}</div>
                            <div className="text-[11px] text-muted-foreground font-mono">{i.sku ?? "—"}{i.location ? ` · ${i.location}` : ""}</div>
                          </td>
                          <td className="px-3 py-2.5 hidden md:table-cell"><Badge variant="outline" className="text-[10px]">{catLabel(i.category)}</Badge></td>
                          <td className="px-3 py-2.5 text-right tabular-nums font-semibold">
                            {i.stockQty} <span className="text-muted-foreground font-normal text-xs">{i.unit}</span>
                            {low && <div className="text-[10px] text-destructive font-semibold">LOW</div>}
                          </td>
                          <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground hidden sm:table-cell">{i.reorderLevel}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden lg:table-cell">{fmtCedi(i.unitCost)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden lg:table-cell">{i.sellPrice ? fmtCedi(i.sellPrice) : <span className="text-muted-foreground">internal</span>}</td>
                          <td className="px-3 py-2.5 text-right">
                            <div className="flex gap-1 justify-end">
                              <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => setMvFor({ item: i, type: "PURCHASE" })}><TrendingUp className="size-3.5" /> In</Button>
                              <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => setMvFor({ item: i, type: "ISSUE" })}><TrendingDown className="size-3.5" /> Issue</Button>
                              <Button size="sm" variant="ghost" className="h-7" onClick={() => setItemDialog(i)}><Pencil className="size-3.5" /></Button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── LOW STOCK ── */}
        <TabsContent value="low">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><PackageOpen className="size-4 text-destructive" /> Below reorder level</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {data.lowStockItems.length === 0 && <EmptyState icon={<Package className="size-5" />} title="Everything is well stocked" />}
              {data.lowStockItems.map((i) => (
                <div key={i.id} className="rounded-lg border border-destructive/30 bg-destructive/5 p-3 flex items-center justify-between gap-3 flex-wrap">
                  <div>
                    <div className="font-medium text-sm">{i.name}</div>
                    <div className="text-xs text-muted-foreground">{i.stockQty} {i.unit} left · reorder at {i.reorderLevel}</div>
                  </div>
                  <Button size="sm" className="h-8 gap-1" onClick={() => setMvFor({ item: data.items.find((x) => x.id === i.id)!, type: "PURCHASE" })}>
                    <Truck className="size-3.5" /> Record restock
                  </Button>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── MOVEMENTS ── */}
        <TabsContent value="movements">
          <Card>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[560px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Item</th>
                      <th className="px-3 py-2.5 font-medium">Type</th>
                      <th className="px-3 py-2.5 font-medium text-right">Qty</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Party</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Note</th>
                      <th className="px-3 py-2.5 font-medium text-right hidden md:table-cell">When</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.movements.map((m) => (
                      <tr key={m.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5 font-medium">{m.item}</td>
                        <td className="px-3 py-2.5">
                          <Badge variant={m.type === "PURCHASE" ? "default" : m.type === "ISSUE" ? (m.partyType === "SALE" ? "secondary" : "destructive") : "secondary"} className="text-[10px]">
                            {m.partyType === "SALE" ? "Sale" : m.type === "PURCHASE" ? "Restock" : m.type === "ISSUE" ? "Issue" : m.type === "RETURN" ? "Return" : "Adjust"}
                          </Badge>
                        </td>
                        <td className={`px-3 py-2.5 text-right tabular-nums font-semibold ${m.qty > 0 ? "text-emerald-600" : "text-destructive"}`}>
                          {m.qty > 0 ? `+${m.qty}` : m.qty}
                        </td>
                        <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground">
                          {m.partyName ?? "—"}{m.chargedToInvoice && <Badge variant="outline" className="ml-1 text-[9px] text-primary border-primary/40">invoiced</Badge>}
                        </td>
                        <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground text-xs">{m.note ?? "—"}</td>
                        <td className="px-3 py-2.5 text-right hidden md:table-cell text-muted-foreground text-xs">{fmtDate(m.createdAt)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── SUPPLIERS ── */}
        <TabsContent value="suppliers">
          <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            {data.suppliers.map((s) => (
              <Card key={s.id}>
                <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Truck className="size-4 text-primary" /> {s.name}</CardTitle></CardHeader>
                <CardContent className="space-y-1.5 text-sm">
                  {s.phone && <div className="text-muted-foreground">{s.phone}</div>}
                  {s.itemsSupplied && <div className="text-xs text-muted-foreground">Supplies: {s.itemsSupplied}</div>}
                  {s.notes && <div className="text-xs text-muted-foreground italic">{s.notes}</div>}
                  <Button size="sm" variant="ghost" className="h-7 gap-1 mt-1 text-destructive" onClick={async () => { await apiPost(`/api/school/${currentSchoolId}/ops/inventory`, { action: "delete-supplier", id: s.id }); toast({ title: "Supplier removed" }); load(); }}>
                    Remove
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>
      </Tabs>

      <ItemDialog item={itemDialog} onOpenChange={(o) => !o && setItemDialog(null)} onDone={() => { setItemDialog(null); load(); }} />
      <MovementDialog ctx={mvFor} students={data.students} suppliers={data.suppliers} onOpenChange={(o) => !o && setMvFor(null)} onDone={() => { setMvFor(null); load(); }} />
      <SupplierDialog supplier={supDialog} onOpenChange={(o) => !o && setSupDialog(null)} onDone={() => { setSupDialog(null); load(); }} />

      {/* sale receipt */}
      <Dialog open={!!saleReceipt} onOpenChange={(o) => !o && setSaleReceipt(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader className="text-center">
            <DialogTitle className="flex items-center justify-center gap-2"><CheckCircle2 className="size-5 text-emerald-600" /> Sale completed</DialogTitle>
            <DialogDescription className="sr-only">Counter sale receipt</DialogDescription>
          </DialogHeader>
          <div className="text-center space-y-1 py-2">
            <div className="text-3xl font-bold text-primary">{fmtCedi(saleReceipt?.total ?? 0)}</div>
            <div className="text-sm">{saleReceipt?.buyer}</div>
            <div className="font-mono text-xs text-muted-foreground">{saleReceipt?.saleNo} · {PAYMENT_LABELS[saleReceipt?.method ?? ""] ?? saleReceipt?.method}</div>
          </div>
          <div className="rounded-lg border divide-y text-sm">
            {(saleReceipt?.lines ?? []).map((l, i) => (
              <div key={i} className="px-3 py-1.5 flex justify-between">
                <span>{l.item} ×{l.qty}</span>
                <span className="tabular-nums">{fmtCedi(l.amount)}</span>
              </div>
            ))}
          </div>
          <DialogFooter className="sm:justify-center">
            <Button size="sm" onClick={() => setSaleReceipt(null)}>Done</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function ItemDialog({ item, onOpenChange, onDone }: { item: Item | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [sku, setSku] = useState("");
  const [category, setCategory] = useState("STATIONERY");
  const [unit, setUnit] = useState("pcs");
  const [unitCost, setUnitCost] = useState("");
  const [sellPrice, setSellPrice] = useState("");
  const [reorderLevel, setReorderLevel] = useState("10");
  const [location, setLocation] = useState("");
  const [initialQty, setInitialQty] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (item && item !== "new") {
      setName(item.name); setSku(item.sku ?? ""); setCategory(item.category); setUnit(item.unit);
      setUnitCost((item.unitCost / 100).toFixed(2)); setSellPrice(item.sellPrice ? (item.sellPrice / 100).toFixed(2) : "");
      setReorderLevel(String(item.reorderLevel)); setLocation(item.location ?? ""); setInitialQty("");
    } else if (item === "new") {
      setName(""); setSku(""); setCategory("STATIONERY"); setUnit("pcs"); setUnitCost(""); setSellPrice(""); setReorderLevel("10"); setLocation(""); setInitialQty("");
    }
  }, [item]);

  async function submit() {
    if (!name || !unitCost) { toast({ title: "Name and unit cost are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/inventory`, {
        action: "save-item",
        id: item && item !== "new" ? item.id : undefined,
        name, sku, category, unit, unitCostCents: Math.round(parseFloat(unitCost) * 100),
        sellPriceCents: sellPrice ? Math.round(parseFloat(sellPrice) * 100) : null,
        reorderLevel: Number(reorderLevel), location, initialQty: Number(initialQty) || 0,
      });
      toast({ title: item === "new" ? "Item added" : "Item updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!item} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{item === "new" ? "New inventory item" : "Edit item"}</DialogTitle>
          <DialogDescription>Set a reorder level to get low-stock warnings.</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2"><Label>Name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Exercise books (40 leaves)" /></div>
          <div className="space-y-1.5"><Label>SKU</Label><Input value={sku} onChange={(e) => setSku(e.target.value)} placeholder="BK-101" /></div>
          <div className="space-y-1.5">
            <Label>Category</Label>
            <Select value={category} onValueChange={setCategory}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>{CATS.map((c) => <SelectItem key={c} value={c}>{catLabel(c)}</SelectItem>)}</SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Unit</Label><Input value={unit} onChange={(e) => setUnit(e.target.value)} placeholder="pcs / set / bag" /></div>
          <div className="space-y-1.5"><Label>Unit cost (GH₵) *</Label><Input type="number" step="0.5" value={unitCost} onChange={(e) => setUnitCost(e.target.value)} /></div>
          <div className="space-y-1.5"><Label>Sell price (GH₵)</Label><Input type="number" step="0.5" value={sellPrice} onChange={(e) => setSellPrice(e.target.value)} placeholder="blank = internal use" /></div>
          <div className="space-y-1.5"><Label>Reorder level</Label><Input type="number" value={reorderLevel} onChange={(e) => setReorderLevel(e.target.value)} /></div>
          <div className="space-y-1.5 col-span-2"><Label>Location</Label><Input value={location} onChange={(e) => setLocation(e.target.value)} placeholder="Store room A" /></div>
          {item === "new" && (
            <div className="space-y-1.5 col-span-2"><Label>Opening stock qty</Label><Input type="number" value={initialQty} onChange={(e) => setInitialQty(e.target.value)} placeholder="0" /></div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save item"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function MovementDialog({ ctx, students, suppliers, onOpenChange, onDone }: { ctx: { item: Item; type: string } | null; students: InventoryData["students"]; suppliers: Supplier[]; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [qty, setQty] = useState("");
  const [unitCost, setUnitCost] = useState("");
  const [partyType, setPartyType] = useState("SUPPLIER");
  const [partyName, setPartyName] = useState("");
  const [studentId, setStudentId] = useState("");
  const [search, setSearch] = useState("");
  const [charged, setCharged] = useState(false);
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  const isIn = ctx?.type === "PURCHASE";
  useEffect(() => {
    if (ctx) { setQty(""); setPartyType(ctx.type === "PURCHASE" ? "SUPPLIER" : "STUDENT"); setPartyName(""); setStudentId(""); setSearch(""); setCharged(false); setNote(""); setUnitCost(ctx.type === "PURCHASE" ? (ctx.item.unitCost / 100).toFixed(2) : ""); }
  }, [ctx]);

  const filteredStudents = students.filter((s) => !search || s.name.toLowerCase().includes(search) || s.classroom.toLowerCase().includes(search));

  async function submit() {
    if (!ctx) return;
    const n = Number(qty);
    if (!n || n <= 0) { toast({ title: "Enter a quantity" }); return; }
    if (ctx.type !== "PURCHASE" && partyType === "STUDENT" && !studentId) { toast({ title: "Choose the learner" }); return; }
    setBusy(true);
    try {
      const student = students.find((s) => s.id === studentId);
      await apiPost(`/api/school/${currentSchoolId}/ops/inventory`, {
        action: "movement", itemId: ctx.item.id, type: ctx.type, qty: n,
        unitCostCents: unitCost ? Math.round(parseFloat(unitCost) * 100) : undefined,
        partyType, partyName: partyType === "STUDENT" ? student?.name : partyName || undefined,
        studentId: partyType === "STUDENT" ? studentId : undefined,
        chargedToInvoice: charged, note: note || undefined,
      });
      toast({
        title: isIn ? "Stock received" : "Item issued",
        description: isIn ? `${n} ${ctx.item.unit} of ${ctx.item.name} added.` : `${n} ${ctx.item.unit} of ${ctx.item.name} issued${charged ? " and charged to the learner's invoice" : ""}.`,
      });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!ctx} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            {isIn ? <TrendingUp className="size-4 text-emerald-600" /> : <TrendingDown className="size-4 text-destructive" />}
            {isIn ? "Receive stock" : "Issue stock"} — {ctx?.item.name}
          </DialogTitle>
          <DialogDescription>
            {ctx?.item.stockQty} {ctx?.item.unit} currently in stock{!isIn && ` · reorder at ${ctx?.item.reorderLevel}`}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Quantity ({ctx?.item.unit}) *</Label>
              <Input type="number" value={qty} onChange={(e) => setQty(e.target.value)} placeholder={isIn ? "50" : "2"} />
            </div>
            {isIn ? (
              <div className="space-y-1.5">
                <Label>Unit cost (GH₵)</Label>
                <Input type="number" step="0.5" value={unitCost} onChange={(e) => setUnitCost(e.target.value)} />
              </div>
            ) : (
              <div className="space-y-1.5">
                <Label>Charged to invoice?</Label>
                <div className="flex gap-2 pt-1.5">
                  {[true, false].map((v) => (
                    <button key={String(v)} type="button" onClick={() => setCharged(v)}
                      className={`px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors ${charged === v ? "bg-primary text-primary-foreground border-primary" : "hover:bg-secondary"}`}>
                      {v ? "Yes — bill it" : "No"}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>
          <div className="space-y-1.5">
            <Label>{isIn ? "Received from" : "Issued to"}</Label>
            <Select value={partyType} onValueChange={setPartyType}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                {isIn ? <SelectItem value="SUPPLIER">Supplier</SelectItem> : <>
                  <SelectItem value="STUDENT">Learner</SelectItem>
                  <SelectItem value="STAFF">Staff</SelectItem>
                  <SelectItem value="CLASS">Class / department</SelectItem>
                </>}
              </SelectContent>
            </Select>
          </div>
          {partyType === "SUPPLIER" && (
            <div className="space-y-1.5">
              <Label>Supplier</Label>
              <Select value={partyName} onValueChange={setPartyName}>
                <SelectTrigger><SelectValue placeholder="Choose supplier" /></SelectTrigger>
                <SelectContent>
                  {suppliers.map((s) => <SelectItem key={s.id} value={s.name}>{s.name}</SelectItem>)}
                  <SelectItem value="Other">Other / ad-hoc</SelectItem>
                </SelectContent>
              </Select>
            </div>
          )}
          {partyType === "STUDENT" && (
            <div className="space-y-1.5">
              <Label>Learner *</Label>
              <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} className="mb-1" />
              <div className="border rounded-lg max-h-36 overflow-y-auto nice-scroll">
                {filteredStudents.slice(0, 30).map((s) => (
                  <button key={s.id} type="button" onClick={() => setStudentId(s.id)}
                    className={`w-full flex justify-between px-3 py-1.5 border-b last:border-0 text-sm text-left ${studentId === s.id ? "bg-primary text-primary-foreground" : "hover:bg-secondary/50"}`}>
                    <span>{s.name}</span><span className="text-xs opacity-70">{s.classroom}</span>
                  </button>
                ))}
              </div>
            </div>
          )}
          {partyType !== "STUDENT" && partyType !== "SUPPLIER" && (
            <div className="space-y-1.5">
              <Label>Name</Label>
              <Input value={partyName} onChange={(e) => setPartyName(e.target.value)} placeholder="e.g. Staff common room / Basic 4 — Gold" />
            </div>
          )}
          <div className="space-y-1.5">
            <Label>Note</Label>
            <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder="optional" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy} variant={isIn ? "default" : "destructive"}>
            {busy ? "Recording…" : isIn ? "Receive stock" : "Issue items"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function SupplierDialog({ supplier, onOpenChange, onDone }: { supplier: Supplier | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState("");
  const [itemsSupplied, setItemsSupplied] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (supplier && supplier !== "new") { setName(supplier.name); setPhone(supplier.phone ?? ""); setEmail(supplier.email ?? ""); setItemsSupplied(supplier.itemsSupplied ?? ""); }
    else if (supplier === "new") { setName(""); setPhone(""); setEmail(""); setItemsSupplied(""); }
  }, [supplier]);

  async function submit() {
    if (!name) { toast({ title: "Name is required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/inventory`, {
        action: "save-supplier", id: supplier && supplier !== "new" ? supplier.id : undefined, name, phone, email, itemsSupplied,
      });
      toast({ title: "Supplier saved" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!supplier} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader><DialogTitle>{supplier === "new" ? "New supplier" : "Edit supplier"}</DialogTitle></DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2"><Label>Name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Adom Bookshop & Stationery" /></div>
          <div className="space-y-1.5"><Label>Phone</Label><Input value={phone} onChange={(e) => setPhone(e.target.value)} /></div>
          <div className="space-y-1.5"><Label>Email</Label><Input value={email} onChange={(e) => setEmail(e.target.value)} /></div>
          <div className="space-y-1.5 col-span-2"><Label>What they supply</Label><Input value={itemsSupplied} onChange={(e) => setItemsSupplied(e.target.value)} placeholder="Books, stationery" /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save supplier"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
