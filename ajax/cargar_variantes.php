<?php
require_once '../config/config.php';
requerirLogin();
requerirAdmin();

$db = getDB();
$producto_id = (int) $_GET['producto_id'];

$result = $db->query("SELECT * FROM producto_variantes WHERE producto_id = $producto_id ORDER BY es_variante_principal DESC, precio ASC");
$variantes = $result->fetch_all(MYSQLI_ASSOC);

if (empty($variantes)) {
    echo '<div class="alert alert-info">No hay variantes creadas. Haz clic en "Agregar Variante" para empezar.</div>';
    exit;
}
?>

<table class="table table-hover">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>SKU</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Estado</th>
            <th>Principal</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($variantes as $v): ?>
        <tr>
            <td><?php echo htmlspecialchars($v['nombre_variante']); ?></td>
            <td><code><?php echo htmlspecialchars($v['sku'] ?? 'N/A'); ?></code></td>
            <td><strong>$<?php echo number_format($v['precio'], 0); ?></strong></td>
            <td>
                <span class="badge bg-<?php echo $v['stock'] > 50 ? 'success' : ($v['stock'] > 20 ? 'warning' : 'danger'); ?>">
                    <?php echo $v['stock']; ?>
                </span>
            </td>
            <td>
                <span class="badge bg-<?php echo $v['estado'] == 'disponible' ? 'success' : 'secondary'; ?>">
                    <?php echo ucfirst($v['estado']); ?>
                </span>
            </td>
            <td>
                <?php if ($v['es_variante_principal']): ?>
                    <i class="bi bi-star-fill text-warning"></i>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" 
                    onclick='editarVariante(<?php echo json_encode($v); ?>)'>
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" 
                    onclick="eliminarVariante(<?php echo $v['id']; ?>, <?php echo $v['producto_id']; ?>, '<?php echo htmlspecialchars($v['nombre_variante']); ?>')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>