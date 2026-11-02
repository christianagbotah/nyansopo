"use client"

import * as React from "react"
import * as SelectPrimitive from "@radix-ui/react-select"
import { CheckIcon, ChevronDownIcon, ChevronUpIcon, SearchIcon, XIcon } from "lucide-react"

import { cn } from "@/lib/utils"

// ─────────────────────────────────────────────────────────────
// SEARCHABLE SELECTS
// Any <Select> whose dropdown carries ≥ SEARCH_THRESHOLD options
// automatically gets a filter box pinned at the top of its list:
// type to narrow (matches on the item's visible text), Enter picks
// the first match, ↑/↓ walk the filtered list, Esc closes, ✕ clears.
// Implemented inside the primitive so every call site in the app
// becomes searchable with zero changes.
// ─────────────────────────────────────────────────────────────
const SEARCH_THRESHOLD = 7

const SelectFilterContext = React.createContext("")
// whether the dropdown is currently open — Radix keeps the closed content
// subtree mounted in a detached DocumentFragment, so the filter component
// survives open/close cycles; this context tells it when a NEW open cycle
// begins so it can reset the query and take focus.
const SelectOpenContext = React.createContext(false)

/** Count <SelectItem> elements in a children tree (walks groups, fragments and .map() arrays). */
function countSelectItems(children: React.ReactNode): number {
  let n = 0
  React.Children.forEach(children, (child) => {
    if (!React.isValidElement(child)) return
    if (child.type === SelectItem) {
      n += 1
      return
    }
    // walk into wrapper elements (SelectGroup, fragments, …)
    if (typeof child.type !== "string" && (child.props as { children?: React.ReactNode })?.children) {
      n += countSelectItems((child.props as { children: React.ReactNode }).children)
    }
  })
  return n
}

function Select({
  onOpenChange,
  defaultOpen,
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Root>) {
  const [open, setOpen] = React.useState(!!defaultOpen)
  return (
    <SelectOpenContext.Provider value={open}>
      <SelectPrimitive.Root
        data-slot="select"
        defaultOpen={defaultOpen}
        onOpenChange={(o) => {
          setOpen(o)
          onOpenChange?.(o)
        }}
        {...props}
      />
    </SelectOpenContext.Provider>
  )
}

function SelectGroup({
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Group>) {
  return <SelectPrimitive.Group data-slot="select-group" {...props} />
}

function SelectValue({
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Value>) {
  return <SelectPrimitive.Value data-slot="select-value" {...props} />
}

function SelectTrigger({
  className,
  size = "default",
  children,
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Trigger> & {
  size?: "sm" | "default"
}) {
  return (
    <SelectPrimitive.Trigger
      data-slot="select-trigger"
      data-size={size}
      className={cn(
        "border-input data-[placeholder]:text-muted-foreground [&_svg:not([class*='text-'])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 dark:hover:bg-input/50 flex w-fit items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 data-[size=default]:h-9 data-[size=sm]:h-8 *:data-[slot=select-value]:line-clamp-1 *:data-[slot=select-value]:flex *:data-[slot=select-value]:items-center *:data-[slot=select-value]:gap-2 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
        className
      )}
      {...props}
    >
      {children}
      <SelectPrimitive.Icon asChild>
        <ChevronDownIcon className="size-4 opacity-50" />
      </SelectPrimitive.Icon>
    </SelectPrimitive.Trigger>
  )
}

function SelectContent({
  className,
  children,
  position = "popper",
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Content>) {
  const itemCount = React.useMemo(() => countSelectItems(children), [children])
  const searchable = itemCount >= SEARCH_THRESHOLD

  const listBody = (
    <>
      <SelectScrollUpButton />
      <SelectPrimitive.Viewport
        className={cn(
          "p-1",
          position === "popper" &&
            "h-[var(--radix-select-trigger-height)] w-full min-w-[var(--radix-select-trigger-width)] scroll-my-1"
        )}
      >
        {children}
      </SelectPrimitive.Viewport>
      <SelectScrollDownButton />
    </>
  )

  return (
    <SelectPrimitive.Portal>
      <SelectPrimitive.Content
        data-slot="select-content"
        className={cn(
          "bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 relative z-50 max-h-(--radix-select-content-available-height) min-w-[8rem] origin-(--radix-select-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-md border shadow-md",
          position === "popper" &&
            "data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1",
          className
        )}
        position={position}
        {...props}
      >
        {searchable ? <SelectSearchScope>{listBody}</SelectSearchScope> : listBody}
      </SelectPrimitive.Content>
    </SelectPrimitive.Portal>
  )
}

/** Owns the filter state. Rendered INSIDE the Radix content subtree, so it
 *  mounts fresh every time the dropdown opens (query resets, filter box
 *  re-focuses) and unmounts when it closes. */
function SelectSearchScope({ children }: { children: React.ReactNode }) {
  const [query, setQuery] = React.useState("")
  const [isEmpty, setIsEmpty] = React.useState(false)
  const searchRef = React.useRef<HTMLInputElement>(null)
  const trimmed = query.trim().toLowerCase()

  // after each query change, check the live DOM for visible options — DOM
  // truth beats fiber bookkeeping across Radix's detached-fragment duality
  React.useEffect(() => {
    if (trimmed === "") {
      setIsEmpty(false)
      return
    }
    const raf = requestAnimationFrame(() => {
      const listbox = searchRef.current?.closest('[role="listbox"]')
      setIsEmpty(!listbox || listbox.querySelectorAll('[role="option"]').length === 0)
    })
    return () => cancelAnimationFrame(raf)
  }, [trimmed])

  // Radix focuses the selected item once the popper has positioned (its own
  // effect) — re-assert focus on the filter box one frame later so typing
  // immediately filters instead of hitting the old single-key typeahead.
  const isOpen = React.useContext(SelectOpenContext)
  const openedAtRef = React.useRef(0)
  const interactedRef = React.useRef(false)
  React.useEffect(() => {
    if (!isOpen) {
      interactedRef.current = false
      return
    }
    openedAtRef.current = Date.now()
    // fresh open cycle: clear any stale query from the previous open
    setQuery((q) => (q === "" ? q : ""))
    const raf = requestAnimationFrame(() => searchRef.current?.focus())
    // Radix re-positions the popper asynchronously (floating-ui) and then
    // focuses the selected item — steal focus back onto the filter box until
    // the user actually interacts (types / clicks / navigates) or 800ms pass.
    const onFocus = (e: FocusEvent) => {
      const t = e.target as HTMLElement | null
      if (
        !interactedRef.current &&
        t &&
        t !== searchRef.current &&
        t.getAttribute?.("role") === "option" &&
        Date.now() - openedAtRef.current < 800
      ) {
        searchRef.current?.focus()
      }
    }
    const markInteracted = () => {
      interactedRef.current = true
    }
    document.addEventListener("focus", onFocus, true)
    document.addEventListener("pointerdown", markInteracted, true)
    document.addEventListener("keydown", markInteracted, true)
    return () => {
      cancelAnimationFrame(raf)
      document.removeEventListener("focus", onFocus, true)
      document.removeEventListener("pointerdown", markInteracted, true)
      document.removeEventListener("keydown", markInteracted, true)
    }
  }, [isOpen])

  const pickFirstVisible = (input: HTMLInputElement) => {
    const listbox = input.closest('[role="listbox"]')
    const first = listbox?.querySelector<HTMLElement>('[role="option"]:not([data-disabled])')
    if (!first) return false
    if (typeof PointerEvent !== "undefined") {
      first.dispatchEvent(new PointerEvent("pointerdown", { bubbles: true, pointerType: "mouse" }))
      first.dispatchEvent(new PointerEvent("pointerup", { bubbles: true, pointerType: "mouse" }))
    } else {
      first.click()
    }
    return true
  }

  return (
    <SelectFilterContext.Provider value={trimmed}>
        <div className="bg-popover sticky top-0 z-10 border-b border-border/70 p-1.5">
          <div className="relative">
            <SearchIcon className="pointer-events-none absolute top-1/2 left-2 size-3.5 -translate-y-1/2 text-muted-foreground" />
            <input
              ref={searchRef}
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === "Enter") {
                  // pick the first visible option (same path as a mouse click)
                  if (pickFirstVisible(e.currentTarget)) e.preventDefault()
                  e.stopPropagation()
                } else if (e.key === "Escape") {
                  // let it bubble so Radix closes the popup
                } else if (e.key === "Backspace" || e.key.length === 1) {
                  // keep Radix's single-key typeahead away from our input
                  e.stopPropagation()
                }
                // ↑ ↓ Home End: bubble to Radix → focus first/last visible item
              }}
              placeholder="Type to filter…"
              aria-label="Filter options"
              data-slot="select-filter"
              className="h-8 w-full rounded-md border border-input bg-transparent pr-7 pl-7 text-sm outline-none focus:border-ring focus:ring-[3px] focus:ring-ring/50"
            />
            {query !== "" && (
              <button
                type="button"
                tabIndex={-1}
                aria-label="Clear filter"
                onClick={() => {
                  setQuery("")
                  searchRef.current?.focus()
                }}
                className="absolute top-1/2 right-1.5 -translate-y-1/2 rounded-sm p-0.5 text-muted-foreground transition-colors hover:text-foreground"
              >
                <XIcon className="size-3.5" />
              </button>
            )}
          </div>
        </div>
        {children}
        {trimmed !== "" && isEmpty && (
          <div className="py-4 text-center text-sm text-muted-foreground">No matching option</div>
        )}
    </SelectFilterContext.Provider>
  )
}

function SelectLabel({
  className,
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Label>) {
  return (
    <SelectPrimitive.Label
      data-slot="select-label"
      className={cn("text-muted-foreground px-2 py-1.5 text-xs", className)}
      {...props}
    />
  )
}

function SelectItem({
  className,
  children,
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Item>) {
  const query = React.useContext(SelectFilterContext)
  // item's searchable text: string children up-front, otherwise measured from the DOM on mount
  const [text, setText] = React.useState<string | null>(() =>
    typeof children === "string" ? children : null
  )
  const refCb = React.useCallback((node: HTMLElement | null) => {
    if (node) setText((prev) => prev ?? node.textContent ?? "")
  }, [])

  const hidden = query !== "" && text !== null && !text.toLowerCase().includes(query)
  if (hidden) return null

  return (
    <SelectPrimitive.Item
      data-slot="select-item"
      ref={refCb}
      className={cn(
        "focus:bg-accent focus:text-accent-foreground [&_svg:not([class*='text-'])]:text-muted-foreground relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 *:[span]:last:flex *:[span]:last:items-center *:[span]:last:gap-2",
        className
      )}
      {...props}
    >
      <span className="absolute right-2 flex size-3.5 items-center justify-center">
        <SelectPrimitive.ItemIndicator>
          <CheckIcon className="size-4" />
        </SelectPrimitive.ItemIndicator>
      </span>
      <SelectPrimitive.ItemText>{children}</SelectPrimitive.ItemText>
    </SelectPrimitive.Item>
  )
}

function SelectSeparator({
  className,
  ...props
}: React.ComponentProps<typeof SelectPrimitive.Separator>) {
  return (
    <SelectPrimitive.Separator
      data-slot="select-separator"
      className={cn("bg-border pointer-events-none -mx-1 my-1 h-px", className)}
      {...props}
    />
  )
}

function SelectScrollUpButton({
  className,
  ...props
}: React.ComponentProps<typeof SelectPrimitive.ScrollUpButton>) {
  return (
    <SelectPrimitive.ScrollUpButton
      data-slot="select-scroll-up-button"
      className={cn(
        "flex cursor-default items-center justify-center py-1",
        className
      )}
      {...props}
    >
      <ChevronUpIcon className="size-4" />
    </SelectPrimitive.ScrollUpButton>
  )
}

function SelectScrollDownButton({
  className,
  ...props
}: React.ComponentProps<typeof SelectPrimitive.ScrollDownButton>) {
  return (
    <SelectPrimitive.ScrollDownButton
      data-slot="select-scroll-down-button"
      className={cn(
        "flex cursor-default items-center justify-center py-1",
        className
      )}
      {...props}
    >
      <ChevronDownIcon className="size-4" />
    </SelectPrimitive.ScrollDownButton>
  )
}

export {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectScrollDownButton,
  SelectScrollUpButton,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
}
