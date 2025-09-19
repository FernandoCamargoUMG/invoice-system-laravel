<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Listar todos los clientes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Búsqueda
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Paginación
        $customers = $query->paginate($request->get('per_page', 15));

        return response()->json($customers);
    }

    /**
     * Crear nuevo cliente
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:15',
                'address' => 'nullable|string|max:150'
            ]);

            $customer = Customer::create($validatedData);

            return response()->json([
                'message' => 'Cliente creado exitosamente',
                'customer' => $customer
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Mostrar cliente específico
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load('invoices');
        return response()->json($customer);
    }

    /**
     * Actualizar cliente
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:15',
                'address' => 'nullable|string|max:150'
            ]);

            $customer->update($validatedData);

            return response()->json([
                'message' => 'Cliente actualizado exitosamente',
                'customer' => $customer
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Eliminar cliente
     */
    public function destroy(Customer $customer): JsonResponse
    {
        if ($customer->invoices()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el cliente porque tiene facturas asociadas'
            ], 409);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }
}