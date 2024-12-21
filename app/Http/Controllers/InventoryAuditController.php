<?php

namespace App\Http\Controllers;

use App\Models\InventoryAudit;
use App\Models\InventoryAuditItem;
use App\Services\InventoryAuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryAuditController extends Controller
{
    protected $auditService;

    public function __construct(InventoryAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function index()
    {
        $audits = InventoryAudit::with(['creator', 'completedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Dashboard/Inventory/Audits/Index', [
            'audits' => $audits
        ]);
    }

    public function create()
    {
        return Inertia::render('Dashboard/Inventory/Audits/Create');
    }

    public function store(Request $request)
    {
        try {
            $audit = $this->auditService->createAudit();
            return redirect()->route('inventory-audits.show', $audit)
                ->with('success', 'Инвентаризация создана');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $audit = InventoryAudit::with([
                'items.item',
                'items.unit',
                'items.countedBy',
                'creator',
                'completedBy'
            ])->findOrFail($id);

            \Log::info('Loading audit:', [
                'id' => $audit->id,
                'number' => $audit->number,
                'status' => $audit->status,
                'items_count' => $audit->items->count()
            ]);

            $auditData = $audit->toArray();
            $items = $audit->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'item_id' => $item->item_id,
                    'unit_id' => $item->unit_id,
                    'expected_quantity' => $item->expected_quantity,
                    'actual_quantity' => $item->actual_quantity,
                    'difference' => $item->difference,
                    'cost_per_unit' => $item->cost_per_unit,
                    'difference_cost' => $item->difference_cost,
                    'notes' => $item->notes,
                    'counted_by' => $item->countedBy,
                    'counted_at' => $item->counted_at,
                    'item' => $item->item,
                    'unit' => $item->unit
                ];
            })->all();

            return Inertia::render('Dashboard/Inventory/Audits/Show', [
                'audit' => array_merge($auditData, ['items' => $items])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in show method: ' . $e->getMessage(), [
                'audit_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('inventory-audits.index')
                ->with('error', 'Инвентаризация не найдена');
        }
    }

    public function start(InventoryAudit $audit)
    {
        try {
            $this->auditService->startAudit($audit);
            return back()->with('success', 'Инвентаризация начата');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateQuantity(Request $request, InventoryAuditItem $item)
    {
        $request->validate([
            'actual_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            $this->auditService->updateItemQuantity(
                $item,
                $request->actual_quantity,
                $request->notes
            );

            return back()->with('success', 'Количество обновлено');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request, InventoryAudit $audit)
    {
        try {
            $this->auditService->completeAudit($audit, $request->boolean('update_stock', true));
            return back()->with('success', 'Инвентаризация завершена');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(InventoryAudit $audit)
    {
        try {
            $this->auditService->cancelAudit($audit);
            return back()->with('success', 'Инвентаризация отменена');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
} 