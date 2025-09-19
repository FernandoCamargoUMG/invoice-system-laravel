<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Factura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .invoice-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
        }
        .total-row {
            margin: 5px 0;
        }
        .total-final {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nueva Factura Creada</h1>
        <p>Factura #INV-{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="invoice-info">
        <h3>Información del Cliente</h3>
        <p><strong>Nombre:</strong> {{ $customer->name }}</p>
        <p><strong>Email:</strong> {{ $customer->email }}</p>
        <p><strong>Teléfono:</strong> {{ $customer->phone }}</p>
        <p><strong>Dirección:</strong> {{ $customer->address }}</p>
    </div>

    <div class="invoice-info">
        <h3>Detalles de la Factura</h3>
        <p><strong>Fecha:</strong> {{ $invoice->created_at->format('d/m/Y H:i') }}</p>
        <p><strong>Estado:</strong> 
            @switch($invoice->status)
                @case('pending')
                    <span style="color: #ffc107;">Pendiente</span>
                    @break
                @case('paid')
                    <span style="color: #28a745;">Pagado</span>
                    @break
                @case('partial')
                    <span style="color: #17a2b8;">Pago Parcial</span>
                    @break
                @case('cancelled')
                    <span style="color: #dc3545;">Cancelado</span>
                    @break
                @default
                    {{ $invoice->status }}
            @endswitch
        </p>
    </div>

    <h3>Productos/Servicios</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product->name }}</strong>
                    @if($item->product->description)
                        <br><small>{{ $item->product->description }}</small>
                    @endif
                </td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->price, 2) }}</td>
                <td>${{ number_format($item->quantity * $item->price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <strong>Subtotal: ${{ number_format($invoice->subtotal, 2) }}</strong>
        </div>
        <div class="total-row">
            <strong>Impuestos: ${{ number_format($invoice->tax_amount, 2) }}</strong>
        </div>
        <div class="total-row total-final">
            <strong>TOTAL: ${{ $total }}</strong>
        </div>
    </div>

    <div class="footer">
        <p>Gracias por su preferencia.</p>
        <p><small>Este es un email automático, por favor no responda a esta dirección.</small></p>
    </div>
</body>
</html>