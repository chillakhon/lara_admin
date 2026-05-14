<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShipmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/delivery/shipments",
     *     operationId="getShipments",
     *     tags={"Shipments"},
     *     summary="Get a list of shipments with optional filters for status and search",
     *     description="Retrieve a paginated list of shipments. You can filter by status and search by tracking number or order number.",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by shipment status ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by tracking number or order number",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of shipments successfully retrieved",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Shipment")
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid filter parameters",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid status ID or search query")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function index(Request $request)
    {
        $query = Shipment::with(['order', 'deliveryMethod', 'status'])
            ->latest();

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status_id', $request->status);
        }

        // Поиск по номеру отслеживания или номеру заказа
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function($q) use ($search) {
                        $q->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        // Пагинация результатов
        $shipments = $query->paginate(15);

        return response()->json([
            'data' => $shipments->items(),
            'links' => $shipments->links(),
            'meta' => $shipments->toArray()['meta']
        ]);
    }


    /**
     * @OA\Put(
     *     path="/api/delivery/shipments/{shipment}",
     *     operationId="updateShipment",
     *     tags={"Shipments"},
     *     summary="Update a shipment's status, tracking number, and notes",
     *     description="Update the status, tracking number, and notes of an existing shipment.",
     *     @OA\Parameter(
     *         name="shipment",
     *         in="path",
     *         required=true,
     *         description="ID of the shipment to be updated",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status_id"},
     *             @OA\Property(property="status_id", type="integer", description="ID of the shipment status"),
     *             @OA\Property(property="tracking_number", type="string", description="Tracking number of the shipment"),
     *             @OA\Property(property="notes", type="string", description="Additional notes for the shipment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipment updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Shipment updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid input")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Shipment not found")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:shipment_statuses,id',
            'tracking_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $shipment->update($validated);

        return response()->json([
            'message' => 'Shipment updated successfully',
            'shipment' => $shipment
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/delivery/shipments/{shipment}/print-label",
     *     operationId="printLabel",
     *     tags={"Shipments"},
     *     summary="Print the shipment label",
     *     description="Generate and download the PDF label for the shipment.",
     *     @OA\Parameter(
     *         name="shipment",
     *         in="path",
     *         required=true,
     *         description="ID of the shipment for which the label is to be printed",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF label generated successfully",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary",
     *                 description="Generated PDF file"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid shipment ID",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid shipment ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Shipment not found")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function printLabel(Shipment $shipment)
    {
        $service = $shipment->deliveryMethod->getDeliveryService();
        $pdf = $service->printLabel($shipment);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="label.pdf"');
    }


    /**
     * @OA\Post(
     *     path="/api/delivery/shipments/{shipment}/cancel",
     *     operationId="cancelShipment",
     *     tags={"Shipments"},
     *     summary="Cancel the shipment",
     *     description="Cancel the shipment by interacting with the external delivery service and update its status.",
     *     @OA\Parameter(
     *         name="shipment",
     *         in="path",
     *         required=true,
     *         description="ID of the shipment to be cancelled",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipment cancelled successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Shipment cancelled successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid shipment ID",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid shipment ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Shipment not found")
     *         )
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function cancel(Shipment $shipment)
    {
        // Получаем сервис для доставки
        $service = $shipment->deliveryMethod->getDeliveryService();

        // Выполняем отмену отправления через внешний сервис
        $service->cancelShipment($shipment);

        // Обновляем статус отправления на "отменено"
        $shipment->update([
            'status_id' => ShipmentStatus::where('code', 'cancelled')->first()->id
        ]);

        return response()->json([
            'message' => 'Отправление отменено'
        ], 200);
    }

}
