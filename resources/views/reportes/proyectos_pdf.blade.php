<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Proyectos</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Reporte de Proyectos - Reserva de Tariquía</h1>
    <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Lat</th><th>Lng</th><th>Presupuesto</th></tr>
        </thead>
        <tbody>
            @foreach ($proyectos as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->tipo_label }}</td>
                    <td>{{ $p->estado_label }}</td>
                    <td>{{ $p->latitud }}</td>
                    <td>{{ $p->longitud }}</td>
                    <td>Bs {{ number_format($p->presupuesto ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
