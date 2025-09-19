<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Listar todos los productos
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        // Filtrar por búsqueda
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0'
            ]);

            $product = Product::create($validatedData);

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'product' => $product
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Mostrar producto específico
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0'
            ]);

            $product->update($validatedData);

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'product' => $product
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Eliminar producto
     */
    public function destroy(Product $product): JsonResponse
    {
        if ($product->invoiceItems()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el producto porque está siendo usado en facturas'
            ], 409);
        }

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }

    /**
     * Actualizar stock del producto
     */
    public function updateStock(Request $request, Product $product): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'stock' => 'required|integer|min:0'
            ]);

            $product->update(['stock' => $validatedData['stock']]);

            return response()->json([
                'message' => 'Stock actualizado exitosamente',
                'product' => $product
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }
}