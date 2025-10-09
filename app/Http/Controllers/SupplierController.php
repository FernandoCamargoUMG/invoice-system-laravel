<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    /**
     * Listar todos los proveedores
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Supplier::query();

            // Filtro por búsqueda
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->search($search);
            }

            // Filtro por estado
            if ($request->has('active')) {
                if ($request->boolean('active')) {
                    $query->active();
                } else {
                    $query->inactive();
                }
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $suppliers = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los proveedores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo proveedor
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'nullable|email|max:100|unique:suppliers,email',
                'phone' => 'nullable|string|max:15',
                'address' => 'nullable|string|max:255',
                'contact_person' => 'nullable|string|max:100',
                'tax_id' => 'nullable|string|max:20|unique:suppliers,tax_id',
                'notes' => 'nullable|string'
            ]);

            $supplier = Supplier::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => $supplier
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un proveedor específico
     */
    public function show(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->load(['purchases' => function($query) {
                $query->latest()->take(5);
            }]);

            return response()->json([
                'success' => true,
                'data' => $supplier
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un proveedor
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:100',
                'email' => 'nullable|email|max:100|unique:suppliers,email,' . $supplier->id,
                'phone' => 'nullable|string|max:15',
                'address' => 'nullable|string|max:255',
                'contact_person' => 'nullable|string|max:100',
                'tax_id' => 'nullable|string|max:20|unique:suppliers,tax_id,' . $supplier->id,
                'notes' => 'nullable|string',
                'is_active' => 'sometimes|boolean'
            ]);

            $supplier->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $supplier
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un proveedor
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        try {
            // Verificar si tiene compras asociadas
            if ($supplier->purchases()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el proveedor porque tiene compras asociadas'
                ], 422);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Proveedor eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/desactivar proveedor
     */
    public function toggleStatus(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->update([
                'is_active' => !$supplier->is_active
            ]);

            $status = $supplier->is_active ? 'activado' : 'desactivado';

            return response()->json([
                'success' => true,
                'message' => "Proveedor {$status} exitosamente",
                'data' => $supplier
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener proveedores activos para select
     */
    public function activeSuppliers(): JsonResponse
    {
        try {
            $suppliers = Supplier::active()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone']);

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores activos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}