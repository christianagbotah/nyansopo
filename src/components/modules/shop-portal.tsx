"use client";
// ── wave 77 — School Shop console (shop attendant / bursar / cashier) ──
// The catalog parents order from plus the fulfilment queue:
//   PENDING_PAYMENT → PAID (counter payment here / online via the payments
//   engine) → READY (packed) → DELIVERED; pending/paid orders can be cancelled.
// Money is INTEGER CENTS everywhere — prices/totals below are cents; only the
// dialog inputs deal in GHS and convert with Math.round(x * 100).
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, fmtDateTime, PAYMENT_LABELS } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { canAny } from "@/lib/rbac";
import { AlertTriangle, Banknote, CheckCircle2, Hourglass, Inbox, Loader2, Minus, Package, PackageCheck, Pencil, Plus, Receipt, Store, Trash2, XCircle } from "lucide-react";

type ShopItem = {
  id: string; name: string; category: string; priceCents: number;
  stock: number | null; lowStockAt: number; active: boolean;
  createdAt: string; updatedAt: string;
};
type OrderLine = { id: string; itemId: string | null; name: string; priceCents: number; qty: number };
type PaymentReqLite = { id: string; clientReference: string; status: string; checkoutUrl: string };
type ShopOrder = {
  id: string; orderNo: string; parentId: string; parentName: string; studentId: string;
  student: { id: string; firstName: string; lastName: string; enrollments: { classroom: { name: string } }[] } | null;
  items: OrderLine[]; totalCents: number; status: string;
  paymentMethod: string | null; paymentRequests: PaymentReqLite[];
  paidAt: string | null; paidByName: string | null;
  readyAt: string | null; readyByName: string | null;
  deliveredAt: string | null; deliveredByName: string | null;
  deliveryNote: string | null; cancelReason: string | null;
  createdAt: string; updatedAt: string;
};
type ShopData = {
  items: ShopItem[]; orders: ShopOrder[];
  counts: { pendingPayment: number; paid: number; ready: number; deliveredToday: number };
  lowStock: ShopItem[]; categories: string[];
};

const CATEGORY_LABEL: Record<string, string> = {
  STATIONERY: "Stationery", UNIFORM: "Uniform", BOOKS: "Books", SUPPLIES: "Supplies", OTHER: "Other",
};
const CATEGORY_BADGE: Record<string, string> = {
  STATIONERY: "bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200",
  UNIFORM: "bg-violet-100 text-violet-900 dark:bg-violet-950 dark:text-violet-200",
  BOOKS: "bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200",
  SUPPLIES: "bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200",
  OTHER: "bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-200",
};
const ORDER_LABEL: Record<string, string> = {
  PENDING_PAYMENT: "Awaiting payment", PAID: "Paid", READY: "Packed", DELIVERED: "Delivered", CANCELLED: "Cancelled",
};
// Badge tones matching the solid pills used elsewhere (conductor/library modules)
const ORDER_BADGE: Record<string, string> = {
  PENDING_PAYMENT: "bg-amber-500 text-white",
  PAID: "bg-sky-600 text-white",
  READY: "bg-violet-600 text-white",
  DELIVERED: "bg-emerald-600 text-white",
  CANCELLED: "bg-destructive text-white",
};
const FILTERS: { key: string; label: string }[] = [
  { key: "all", label: "All" },
  { key: "PENDING_PAYMENT", label: "Awaiting payment" },
  { key: "PAID", label: "Paid" },
  { key: "READY", label: "Packed" },
  { key: "DELIVERED", label: "Delivered" },
  { key: "CANCELLED", label: "Cancelled" },
];
const EMPTY_TITLE: Record<string, string> = {
  all: "No orders yet",
  PENDING_PAYMENT: "No orders awaiting payment",
  PAID: "Nothing waiting to be packed",
  READY: "No packed orders waiting for delivery",
  DELIVERED: "No delivered orders yet",
  CANCELLED: "No cancelled orders",
};
const EMPTY_HINT: Record<string, string> = {
  all: "Orders parents place in the parent portal appear here.",
  PENDING_PAYMENT: "Parents are checking out — new orders land here.",
  PAID: "Every paid order has been packed.",
  READY: "Everything packed has been delivered.",
  DELIVERED: "Delivered orders will show here.",
  CANCELLED: "Cancelled orders will show here.",
};
// Counter tender options the server accepts for order.mark-paid
const COUNTER_METHODS = ["CASH", "MOMO_MTN", "MOMO_TELECEL", "MOMO_AT", "BANK", "CHEQUE", "CARD"];

export function ShopPortal() {
  const { currentSchoolId, session } = useStore();
  const canManage = canAny(session?.permissions, ["shop.manage"]);
  const [data, setData] = useState<ShopData | null>(null);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState<string | null>(null);
  const [filter, setFilter] = useState("all");
  const [payOrder, setPayOrder] = useState<ShopOrder | null>(null);
  const [cancelOrder, setCancelOrder] = useState<ShopOrder | null>(null);
  const [itemDialog, setItemDialog] = useState<{ open: boolean; item: ShopItem | null }>({ open: false, item: null });
  const [deleteItem, setDeleteItem] = useState<ShopItem | null>(null);
  const [busyOrder, setBusyOrder] = useState<string | null>(null);
  const [busyItem, setBusyItem] = useState<string | null>(null); // "save" | "toggle-{id}" | "stock-{id}-{delta}"
  const [delBusy, setDelBusy] = useState(false);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<ShopData>(`/api/school/${currentSchoolId}/ops/shop`)
      .then((d) => { setData(d); setErr(null); })
      .catch((e: any) => {
        setErr(e.message);
        toast({ title: "Could not load the school shop", description: e.message, variant: "destructive" });
      })
      .finally(() => setLoading(false));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  const filtered = useMemo(() => {
    if (!data) return [];
    return filter === "all" ? data.orders : data.orders.filter((o) => o.status === filter);
  }, [data, filter]);

  const path = `/api/school/${currentSchoolId}/ops/shop`;

  async function markPaid(order: ShopOrder, method: string) {
    setBusyOrder(order.id);
    try {
      const res = await apiPost<{ ok: boolean; receiptNo: string }>(path, { action: "order.mark-paid", orderId: order.id, method });
      toast({
        title: `Paid — receipt ${res.receiptNo}`,
        description: `${order.orderNo} · ${fmtCedi(order.totalCents)} · ${PAYMENT_LABELS[method] ?? method} — the parent has been notified.`,
      });
      setPayOrder(null);
      load();
    } catch (e: any) {
      toast({ title: "Could not record the payment", description: e.message, variant: "destructive" });
    } finally {
      setBusyOrder(null);
    }
  }

  async function transition(order: ShopOrder, action: "order.ready" | "order.deliver", okTitle: string) {
    setBusyOrder(order.id);
    try {
      await apiPost(path, { action, orderId: order.id });
      toast({ title: okTitle, description: `${order.orderNo} — the parent has been notified.` });
      load();
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
    } finally {
      setBusyOrder(null);
    }
  }

  async function cancelAnOrder(order: ShopOrder, reason: string) {
    setBusyOrder(order.id);
    try {
      await apiPost(path, { action: "order.cancel", orderId: order.id, reason: reason || undefined });
      toast({ title: "Order cancelled", description: `${order.orderNo} — the parent has been notified.` });
      setCancelOrder(null);
      load();
    } catch (e: any) {
      toast({ title: "Could not cancel the order", description: e.message, variant: "destructive" });
    } finally {
      setBusyOrder(null);
    }
  }

  async function saveItem(payload: Record<string, unknown>) {
    const editing = itemDialog.item;
    setBusyItem("save");
    try {
      await apiPost(path, { action: editing ? "item.update" : "item.create", ...payload });
      toast({
        title: editing ? "Item updated" : "Item added to the catalog",
        description: editing ? undefined : "It is live on the parent shop immediately.",
      });
      setItemDialog({ open: false, item: null });
      load();
    } catch (e: any) {
      toast({ title: "Could not save the item", description: e.message, variant: "destructive" });
    } finally {
      setBusyItem(null);
    }
  }

  async function toggleItem(item: ShopItem) {
    setBusyItem(`toggle-${item.id}`);
    try {
      const res = await apiPost<{ item: ShopItem }>(path, { action: "item.toggle", itemId: item.id });
      toast({
        title: res.item.active ? `${item.name} relisted` : `${item.name} delisted`,
        description: res.item.active ? "Parents can order it again." : "It is hidden from the parent shop.",
      });
      load();
    } catch (e: any) {
      toast({ title: "Could not update the item", description: e.message, variant: "destructive" });
    } finally {
      setBusyItem(null);
    }
  }

  async function stockMove(item: ShopItem, delta: number) {
    setBusyItem(`stock-${item.id}-${delta}`);
    try {
      const res = await apiPost<{ item: ShopItem }>(path, { action: "item.stock", itemId: item.id, delta });
      toast({
        title: `Stock updated — ${res.item.stock} on hand`,
        description: `${item.name} ${delta > 0 ? "+" : ""}${delta}`,
      });
      load();
    } catch (e: any) {
      toast({ title: "Could not update stock", description: e.message, variant: "destructive" });
    } finally {
      setBusyItem(null);
    }
  }

  async function confirmDelete() {
    if (!deleteItem) return;
    setDelBusy(true);
    try {
      await apiPost(path, { action: "item.delete", itemId: deleteItem.id });
      toast({ title: "Item deleted", description: `${deleteItem.name} removed from the catalog.` });
      setDeleteItem(null);
      load();
    } catch (e: any) {
      // 409 — the item has order history; the server's message explains delisting instead
      toast({ title: "Could not delete the item", description: e.message, variant: "destructive" });
      setDeleteItem(null);
    } finally {
      setDelBusy(false);
    }
  }

  if (err && !data) {
    return (
      <div>
        <ModuleHeader
          title="School Shop"
          description="Parents order stationery, uniforms and books from the parent portal — record counter payments here, pack orders and deliver them to learners."
        />
        <EmptyState icon={<AlertTriangle className="size-6 text-destructive" />} title="Could not load the school shop" hint={err} />
      </div>
    );
  }
  if (loading || !data) return <LoadingBlock label="Opening the school shop…" />;

  return (
    <div>
      <ModuleHeader
        title="School Shop"
        description="Parents order stationery, uniforms and books from the parent portal — record counter payments here, pack orders and deliver them to learners."
      />

      {/* fulfilment pipeline counters */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Tile icon={<Hourglass className="size-4" />} label="Awaiting payment" value={data.counts.pendingPayment} sub="orders waiting for money" tone={data.counts.pendingPayment ? "warn" : undefined} />
        <Tile icon={<Package className="size-4" />} label="Paid — to pack" value={data.counts.paid} sub="pick and pack these" />
        <Tile icon={<Store className="size-4" />} label="Packed — to deliver" value={data.counts.ready} sub="hand to the learners" />
        <Tile icon={<CheckCircle2 className="size-4" />} label="Delivered today" value={data.counts.deliveredToday} sub="in the last 24 hours" tone={data.counts.deliveredToday ? "good" : undefined} />
      </div>

      {/* low-stock strip */}
      {data.lowStock.length > 0 && (
        <div className="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2.5 mb-4 flex flex-wrap items-center gap-2">
          <AlertTriangle className="size-4 text-amber-600 dark:text-amber-400 shrink-0" />
          <span className="text-sm font-medium text-amber-900 dark:text-amber-200">Low stock — restock soon:</span>
          <div className="flex flex-wrap gap-1.5">
            {data.lowStock.map((i) => (
              <Badge key={i.id} className="bg-amber-500 text-white text-[10px]">{i.name} · {i.stock} left</Badge>
            ))}
          </div>
        </div>
      )}

      <Tabs defaultValue="orders">
        <TabsList>
          <TabsTrigger value="orders" className="gap-1.5">
            Orders
            {data.counts.pendingPayment > 0 && (
              <span className="inline-flex items-center justify-center rounded-full bg-amber-600 px-1.5 min-w-4 h-4 text-[10px] font-bold text-white">{data.counts.pendingPayment}</span>
            )}
          </TabsTrigger>
          <TabsTrigger value="catalog">Catalog ({data.items.length})</TabsTrigger>
        </TabsList>

        {/* ── ORDERS ── */}
        <TabsContent value="orders" className="mt-3 space-y-3">
          <div className="flex flex-wrap gap-2">
            {FILTERS.map((f) => {
              const count = f.key === "all" ? data.orders.length : data.orders.filter((o) => o.status === f.key).length;
              const active = filter === f.key;
              return (
                <button key={f.key} onClick={() => setFilter(f.key)} aria-pressed={active}
                  className={cn("inline-flex min-h-11 items-center gap-1.5 rounded-full border px-4 text-sm transition-colors", active ? "border-primary bg-primary/10 font-medium" : "hover:bg-secondary/50")}>
                  {f.label}
                  <span className={cn("rounded-full px-1.5 text-[11px] tabular-nums", active ? "text-primary" : "text-muted-foreground")}>{count}</span>
                </button>
              );
            })}
          </div>

          {filtered.length ? (
            <div className="space-y-3 max-h-[70vh] overflow-y-auto nice-scroll pr-1">
              {filtered.map((o) => (
                <OrderCard
                  key={o.id} order={o} canManage={canManage} busy={busyOrder === o.id}
                  onPay={() => setPayOrder(o)}
                  onCancel={() => setCancelOrder(o)}
                  onReady={() => transition(o, "order.ready", "Order packed — ready for delivery")}
                  onDeliver={() => transition(o, "order.deliver", "Order delivered to learner")}
                />
              ))}
            </div>
          ) : (
            <EmptyState icon={<Inbox className="size-8" />} title={EMPTY_TITLE[filter] ?? "No orders"} hint={EMPTY_HINT[filter] ?? "Orders parents place in the parent portal appear here."} />
          )}
        </TabsContent>

        {/* ── CATALOG ── */}
        <TabsContent value="catalog" className="mt-3">
          <div className="rounded-xl border bg-card">
            <div className="p-3 border-b flex flex-wrap items-center gap-2">
              <div className="text-sm font-semibold flex items-center gap-2 mr-auto"><Store className="size-4 text-muted-foreground" /> Catalog</div>
              <span className="text-xs text-muted-foreground hidden sm:block">Delisted items are hidden from parents.</span>
              {canManage && (
                <Button className="min-h-11 gap-1.5" onClick={() => setItemDialog({ open: true, item: null })}>
                  <Plus className="size-4" /> Add item
                </Button>
              )}
            </div>
            {data.items.length ? (
              <div className="overflow-x-auto nice-scroll">
                <Table className="min-w-[46rem]">
                  <TableHeader>
                    <TableRow>
                      <TableHead>Item</TableHead>
                      <TableHead className="hidden md:table-cell">Category</TableHead>
                      <TableHead>Price</TableHead>
                      <TableHead>Stock</TableHead>
                      <TableHead className="hidden sm:table-cell">Parent shop</TableHead>
                      <TableHead className="w-24" />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {data.items.map((it) => {
                      const low = it.active && it.stock !== null && it.stock <= it.lowStockAt;
                      const busy = busyItem !== null;
                      return (
                        <TableRow key={it.id} className={cn(!it.active && "opacity-60", low && "bg-amber-500/5 border-l-2 border-l-amber-500")}>
                          <TableCell>
                            <div className="font-medium flex items-center gap-1.5 flex-wrap">
                              {it.name}
                              {low && <Badge className="bg-amber-500 text-white text-[10px]">low</Badge>}
                              {!it.active && <Badge variant="secondary" className="text-[10px]">delisted</Badge>}
                            </div>
                            <div className="md:hidden mt-0.5">
                              <Badge className={cn("text-[10px]", CATEGORY_BADGE[it.category] ?? "")}>{CATEGORY_LABEL[it.category] ?? it.category}</Badge>
                            </div>
                          </TableCell>
                          <TableCell className="hidden md:table-cell">
                            <Badge variant="outline" className={cn("border-transparent text-[10px]", CATEGORY_BADGE[it.category] ?? "")}>{CATEGORY_LABEL[it.category] ?? it.category}</Badge>
                          </TableCell>
                          <TableCell className="tabular-nums">{fmtCedi(it.priceCents)}</TableCell>
                          <TableCell>
                            {it.stock === null ? (
                              <div className="flex items-center gap-1.5">
                                <span className="text-muted-foreground">—</span>
                                <Badge variant="secondary" className="text-[10px]">untracked</Badge>
                              </div>
                            ) : (
                              <div className="space-y-1.5">
                                <div className="font-semibold tabular-nums">{it.stock}</div>
                                {canManage && (
                                  <div className="flex gap-1">
                                    <Button variant="outline" size="sm" className="h-11 w-11 px-0" title="Remove 1 — shrinkage or damage"
                                      disabled={busy || it.stock === 0} onClick={() => stockMove(it, -1)}>
                                      {busyItem === `stock-${it.id}--1` ? <Loader2 className="size-3.5 animate-spin" /> : <Minus className="size-3.5" />}
                                    </Button>
                                    <Button variant="outline" size="sm" className="h-11 w-11 px-0 text-xs" title="Add 1"
                                      disabled={busy} onClick={() => stockMove(it, 1)}>
                                      {busyItem === `stock-${it.id}-1` ? <Loader2 className="size-3.5 animate-spin" /> : "+1"}
                                    </Button>
                                    <Button variant="outline" size="sm" className="h-11 w-11 px-0 text-xs" title="Add 5"
                                      disabled={busy} onClick={() => stockMove(it, 5)}>
                                      {busyItem === `stock-${it.id}-5` ? <Loader2 className="size-3.5 animate-spin" /> : "+5"}
                                    </Button>
                                  </div>
                                )}
                              </div>
                            )}
                          </TableCell>
                          <TableCell className="hidden sm:table-cell">
                            {canManage ? (
                              <Button variant="outline" size="sm" className="h-11 gap-1.5 text-xs" disabled={busy} onClick={() => toggleItem(it)}>
                                {busyItem === `toggle-${it.id}` && <Loader2 className="size-3.5 animate-spin" />}
                                {it.active ? "Delist" : "Relist"}
                              </Button>
                            ) : (
                              <Badge variant={it.active ? "secondary" : "outline"} className="text-[10px]">{it.active ? "listed" : "delisted"}</Badge>
                            )}
                          </TableCell>
                          <TableCell>
                            {canManage && (
                              <div className="flex gap-1">
                                <Button variant="ghost" className="size-11" title="Edit" onClick={() => setItemDialog({ open: true, item: it })}><Pencil className="size-4" /></Button>
                                <Button variant="ghost" className="size-11 text-destructive" title="Delete" onClick={() => setDeleteItem(it)}><Trash2 className="size-4" /></Button>
                              </div>
                            )}
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
            ) : (
              <EmptyState icon={<Store className="size-8" />} title="The catalog is empty" hint="Add stationery, uniforms or books for parents to order." />
            )}
          </div>
        </TabsContent>
      </Tabs>

      <PayDialog open={!!payOrder} order={payOrder} busy={!!payOrder && busyOrder === payOrder.id} onClose={() => setPayOrder(null)} onConfirm={(m) => payOrder && markPaid(payOrder, m)} />
      <CancelDialog open={!!cancelOrder} order={cancelOrder} busy={!!cancelOrder && busyOrder === cancelOrder.id} onClose={() => setCancelOrder(null)} onConfirm={(r) => cancelOrder && cancelAnOrder(cancelOrder, r)} />
      <ItemDialog open={itemDialog.open} item={itemDialog.item} categories={data.categories} busy={busyItem === "save"} onClose={() => setItemDialog({ open: false, item: null })} onSave={saveItem} />

      <Dialog open={!!deleteItem} onOpenChange={(o) => !o && setDeleteItem(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete item</DialogTitle>
            <DialogDescription>
              Remove &ldquo;{deleteItem?.name}&rdquo; from the catalog? This cannot be undone. Items parents have ordered before cannot be deleted — delist them instead.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" className="min-h-11" onClick={() => setDeleteItem(null)}>Keep item</Button>
            <Button variant="destructive" className="min-h-11" disabled={delBusy}
              onClick={confirmDelete}>
              {delBusy && <Loader2 className="size-4 animate-spin" />} Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── order card — counter-friendly: everything on one card, thumb-sized buttons ── */
function OrderCard({ order, canManage, busy, onPay, onCancel, onReady, onDeliver }: {
  order: ShopOrder; canManage: boolean; busy: boolean;
  onPay: () => void; onCancel: () => void; onReady: () => void; onDeliver: () => void;
}) {
  const classroom = order.student?.enrollments[0]?.classroom?.name ?? null;
  const onlineRef = order.paymentRequests[0]?.clientReference ?? null;
  const pendingReq = order.paymentRequests.find((r) => r.status === "PENDING") ?? null;
  return (
    <div className="rounded-xl border bg-card p-4">
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <span className="font-mono font-bold text-sm">{order.orderNo}</span>
          <div className="text-sm mt-0.5">{order.parentName}</div>
          {order.student && (
            <div className="text-xs text-muted-foreground flex items-center gap-1.5 flex-wrap">
              for {order.student.firstName} {order.student.lastName}
              {classroom && <Badge variant="outline" className="text-[10px]">{classroom}</Badge>}
            </div>
          )}
        </div>
        <div className="text-right shrink-0">
          <Badge className={cn("text-[10px]", ORDER_BADGE[order.status] ?? "bg-secondary text-secondary-foreground")}>{ORDER_LABEL[order.status] ?? order.status}</Badge>
          <div className="text-[11px] text-muted-foreground mt-1">{fmtDate(order.createdAt)}</div>
        </div>
      </div>

      <div className="mt-3 space-y-0.5 text-sm">
        {order.items.map((it) => (
          <div key={it.id} className="flex items-baseline justify-between gap-3">
            <span className="min-w-0">{it.qty} × {it.name}</span>
            <span className="tabular-nums text-muted-foreground shrink-0">{fmtCedi(it.priceCents)}</span>
          </div>
        ))}
        <div className="flex items-baseline justify-between gap-3 border-t pt-1.5 mt-1.5 font-semibold">
          <span>Total</span>
          <span className="tabular-nums">{fmtCedi(order.totalCents)}</span>
        </div>
      </div>

      {order.deliveryNote && <p className="mt-2 text-xs italic text-muted-foreground">Delivery note: {order.deliveryNote}</p>}
      {order.cancelReason && <p className="mt-2 text-xs italic text-destructive">Cancelled — {order.cancelReason}</p>}

      {order.status === "PENDING_PAYMENT" && pendingReq && (
        <p className="mt-2 text-[11px] text-muted-foreground">
          parent paying online — reference <span className="font-mono">{pendingReq.clientReference}</span>
        </p>
      )}

      {/* timeline trail */}
      {(order.paidAt || order.readyAt || order.deliveredAt) && (
        <div className="mt-3 space-y-1 border-l-2 border-muted pl-3 ml-0.5 text-xs text-muted-foreground">
          {order.paidAt && (
            <div className="flex items-center gap-1.5 flex-wrap">
              <Receipt className="size-3 shrink-0" />
              <span>Paid {fmtDateTime(order.paidAt)} · {order.paymentMethod === "ONLINE" ? "online" : "at the counter"}{order.paidByName ? ` · ${order.paidByName}` : ""}</span>
            </div>
          )}
          {order.paymentMethod === "ONLINE" && onlineRef && (
            <div className="pl-4 text-[10px] font-mono">ref {onlineRef}</div>
          )}
          {order.readyAt && (
            <div className="flex items-center gap-1.5">
              <PackageCheck className="size-3 shrink-0" />
              <span>Packed {fmtDateTime(order.readyAt)}{order.readyByName ? ` · ${order.readyByName}` : ""}</span>
            </div>
          )}
          {order.deliveredAt && (
            <div className="flex items-center gap-1.5">
              <CheckCircle2 className="size-3 shrink-0" />
              <span>Delivered {fmtDateTime(order.deliveredAt)}{order.deliveredByName ? ` · ${order.deliveredByName}` : ""}</span>
            </div>
          )}
        </div>
      )}

      {/* status actions — delivered/cancelled orders have none */}
      {canManage && order.status !== "DELIVERED" && order.status !== "CANCELLED" && (
        <div className="flex flex-wrap gap-2 mt-3">
          {order.status === "PENDING_PAYMENT" && (
            <>
              <Button className="min-h-11 gap-1.5" disabled={busy} onClick={onPay}>
                <Banknote className="size-4" /> Mark paid (counter)
              </Button>
              <Button variant="outline" className="min-h-11 gap-1.5 text-destructive" disabled={busy} onClick={onCancel}>
                <XCircle className="size-4" /> Cancel order
              </Button>
            </>
          )}
          {order.status === "PAID" && (
            <Button className="min-h-11 gap-1.5" disabled={busy} onClick={onReady}>
              {busy ? <Loader2 className="size-4 animate-spin" /> : <PackageCheck className="size-4" />} Mark packed &amp; ready
            </Button>
          )}
          {order.status === "READY" && (
            <Button className="min-h-11 gap-1.5" disabled={busy} onClick={onDeliver}>
              {busy ? <Loader2 className="size-4 animate-spin" /> : <CheckCircle2 className="size-4" />} Mark delivered
            </Button>
          )}
        </div>
      )}
    </div>
  );
}

function Tile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: "good" | "warn" }) {
  return (
    <div className="rounded-xl border bg-card p-4">
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={cn("mt-1.5 text-xl font-bold tabular-nums", tone === "good" && "text-emerald-600 dark:text-emerald-400", tone === "warn" && "text-amber-600 dark:text-amber-400")}>{value}</div>
      {sub && <div className="text-[11px] text-muted-foreground">{sub}</div>}
    </div>
  );
}

/* ── counter payment dialog — method select (CASH default), receipt toast ── */
function PayDialog({ order, open, busy, onClose, onConfirm }: {
  order: ShopOrder | null; open: boolean; busy: boolean; onClose: () => void; onConfirm: (method: string) => void;
}) {
  const [method, setMethod] = useState("CASH");
  useEffect(() => {
    if (open) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setMethod("CASH");
    }
  }, [open]);
  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>Counter payment</DialogTitle>
          <DialogDescription>Take the money in person, pick how it was tendered — a receipt is issued and the parent is notified.</DialogDescription>
        </DialogHeader>
        {order && (
          <div className="rounded-lg border bg-secondary/40 px-3 py-2 text-sm flex items-center justify-between gap-3">
            <span className="min-w-0"><b className="font-mono">{order.orderNo}</b> · {order.parentName}</span>
            <b className="tabular-nums shrink-0">{fmtCedi(order.totalCents)}</b>
          </div>
        )}
        <div className="space-y-1.5">
          <Label>Tendered as</Label>
          <Select value={method} onValueChange={setMethod}>
            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
            <SelectContent>
              {COUNTER_METHODS.map((m) => <SelectItem key={m} value={m}>{PAYMENT_LABELS[m] ?? m}</SelectItem>)}
            </SelectContent>
          </Select>
        </div>
        <DialogFooter>
          <Button variant="outline" className="min-h-11" onClick={onClose}>Back</Button>
          <Button className="min-h-11" disabled={busy || !order} onClick={() => order && onConfirm(method)}>
            {busy ? <Loader2 className="size-4 animate-spin" /> : <Banknote className="size-4" />} Record payment
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── cancel dialog — optional reason, parent is SMS-notified server-side ── */
function CancelDialog({ order, open, busy, onClose, onConfirm }: {
  order: ShopOrder | null; open: boolean; busy: boolean; onClose: () => void; onConfirm: (reason: string) => void;
}) {
  const [reason, setReason] = useState("");
  useEffect(() => {
    if (open) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setReason("");
    }
  }, [open]);
  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>Cancel order {order?.orderNo}</DialogTitle>
          <DialogDescription>
            The parent is told by SMS{order?.status === "PAID" ? " — a paid order needs a refund or fee credit at the counter" : ""}. Give a short reason so they know why.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-1.5">
          <Label>Reason (optional)</Label>
          <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="e.g. size out of stock" maxLength={300} />
        </div>
        <DialogFooter>
          <Button variant="outline" className="min-h-11" onClick={onClose}>Keep order</Button>
          <Button variant="destructive" className="min-h-11" disabled={busy} onClick={() => onConfirm(reason.trim())}>
            {busy ? <Loader2 className="size-4 animate-spin" /> : <XCircle className="size-4" />} Cancel order
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── catalog item dialog — create/edit; blank stock = untracked ── */
function ItemDialog({ open, item, categories, busy, onClose, onSave }: {
  open: boolean; item: ShopItem | null; categories: string[]; busy: boolean;
  onClose: () => void; onSave: (payload: Record<string, unknown>) => void;
}) {
  const [name, setName] = useState("");
  const [category, setCategory] = useState("STATIONERY");
  const [price, setPrice] = useState("");
  const [stock, setStock] = useState("");
  const [lowStockAt, setLowStockAt] = useState("5");

  useEffect(() => {
    if (open) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setName(item?.name ?? "");
      setCategory(item?.category ?? categories[0] ?? "STATIONERY");
      setPrice(item ? (item.priceCents / 100).toFixed(2) : "");
      setStock(item?.stock != null ? String(item.stock) : "");
      setLowStockAt(String(item?.lowStockAt ?? 5));
    }
  }, [open, item, categories]);

  const priceCents = Math.round(parseFloat(price || "0") * 100);
  const valid = name.trim().length >= 2 && priceCents > 0;

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>{item ? "Edit item" : "Add item"}</DialogTitle>
          <DialogDescription>{item ? "Update the catalog entry — parents see the change immediately." : "A new catalog entry is live on the parent shop as soon as you save."}</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-1.5">
            <Label>Name *</Label>
            <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Exercise Book 40 pages" autoFocus />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Category</Label>
              <Select value={category} onValueChange={setCategory}>
                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                <SelectContent>
                  {categories.map((c) => <SelectItem key={c} value={c}>{CATEGORY_LABEL[c] ?? c}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Price (₵) *</Label>
              <Input type="number" min={0} step="0.01" value={price} onChange={(e) => setPrice(e.target.value)} placeholder="3.00" />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Stock on hand</Label>
              <Input type="number" min={0} value={stock} onChange={(e) => setStock(e.target.value)} placeholder="e.g. 40" />
              <p className="text-[11px] text-muted-foreground">Leave blank for items not tracked, e.g. tailoring.</p>
            </div>
            <div className="space-y-1.5">
              <Label>Low-stock alert at</Label>
              <Input type="number" min={0} value={lowStockAt} onChange={(e) => setLowStockAt(e.target.value)} />
              <p className="text-[11px] text-muted-foreground">The shop flags the item when stock drops to this number.</p>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" className="min-h-11" onClick={onClose}>Cancel</Button>
          <Button className="min-h-11" disabled={busy || !valid}
            onClick={() => onSave({
              itemId: item?.id,
              name: name.trim(),
              category,
              priceCents,
              stock: stock.trim() === "" ? null : Math.max(0, Math.round(Number(stock))),
              lowStockAt: Math.max(0, Math.round(Number(lowStockAt) || 5)),
            })}>
            {busy && <Loader2 className="size-4 animate-spin" />} {item ? "Save changes" : "Add item"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
