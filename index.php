<?php
session_start();
require __DIR__ . '/config.php';

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'La solicitud no pudo validarse. Recarga la página e inténtalo de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            if ($accion === 'crear') {
                $direccion = trim($_POST['direccion'] ?? '');
                $sencilla = filter_var($_POST['cantidad_sencilla'] ?? null, FILTER_VALIDATE_INT);
                $doble = filter_var($_POST['cantidad_doble'] ?? null, FILTER_VALIDATE_INT);
                $mixta = filter_var($_POST['cantidad_mixta'] ?? null, FILTER_VALIDATE_INT);

                if ($direccion === '' || mb_strlen($direccion) > 255) {
                    throw new RuntimeException('Ingresa una dirección válida (máximo 255 caracteres).');
                }
                foreach ([$sencilla, $doble, $mixta] as $cantidad) {
                    if ($cantidad === false || $cantidad < 0 || $cantidad > 10000) {
                        throw new RuntimeException('Las cantidades deben ser números enteros entre 0 y 10000.');
                    }
                }
                if (($sencilla + $doble + $mixta) < 1) {
                    throw new RuntimeException('El pedido debe incluir al menos una hamburguesa.');
                }

                $stmt = $conn->prepare('INSERT INTO pedidos (direccion, cantidad_sencilla, cantidad_doble, cantidad_mixta) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('siii', $direccion, $sencilla, $doble, $mixta);
                $stmt->execute();
                header('Location: index.php?mensaje=creado');
                exit;
            } elseif ($accion === 'estado') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $estado = $_POST['estado'] ?? '';
                if (!$id || !in_array($estado, ['preparando', 'despachado'], true)) {
                    throw new RuntimeException('Datos inválidos para actualizar el estado.');
                }
                $stmt = $conn->prepare('UPDATE pedidos SET estado = ? WHERE id = ?');
                $stmt->bind_param('si', $estado, $id);
                $stmt->execute();
                header('Location: index.php?mensaje=actualizado');
                exit;
            } elseif ($accion === 'eliminar') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id) throw new RuntimeException('Pedido no válido.');
                $stmt = $conn->prepare('DELETE FROM pedidos WHERE id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                header('Location: index.php?mensaje=eliminado');
                exit;
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $error = 'Ocurrió un error al guardar los datos. Inténtalo nuevamente.';
        }
    }
}

if (isset($_GET['mensaje'])) {
    $mensajes = [
        'creado' => 'Pedido registrado correctamente.',
        'actualizado' => 'Estado del pedido actualizado.',
        'eliminado' => 'Pedido eliminado correctamente.'
    ];
    $mensaje = $mensajes[$_GET['mensaje']] ?? '';
}

$filtro = $_GET['estado'] ?? '';
if (in_array($filtro, ['preparando', 'despachado'], true)) {
    $stmt = $conn->prepare('SELECT * FROM pedidos WHERE estado = ? ORDER BY creado_en DESC, id DESC');
    $stmt->bind_param('s', $filtro);
    $stmt->execute();
    $pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $pedidos = $conn->query('SELECT * FROM pedidos ORDER BY creado_en DESC, id DESC')->fetch_all(MYSQLI_ASSOC);
}
$totalPedidos = count($pedidos);
$totalUnidades = 0;
foreach ($pedidos as $p) $totalUnidades += (int)$p['cantidad_sencilla'] + (int)$p['cantidad_doble'] + (int)$p['cantidad_mixta'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestión de pedidos | El Sabor</title>
<style>
:root{--bg:#f5f7fb;--ink:#1d2738;--muted:#6d7788;--red:#c9362b;--red2:#a92820;--line:#e4e8f0;--green:#18794e}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,Segoe UI,Arial,sans-serif}
header{background:linear-gradient(120deg,#a92820,#e34a35);color:white;padding:28px 20px}
.wrap{max-width:1120px;margin:auto;padding:0 18px}header .wrap{display:flex;align-items:center;gap:16px}
.logo{font-size:35px}h1{margin:0;font-size:27px}header p{margin:5px 0 0;opacity:.9}
main{padding:24px 0 50px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
.stat,.panel{background:white;border:1px solid var(--line);border-radius:15px;padding:19px;box-shadow:0 4px 18px #1b2a4410}
.stat small{color:var(--muted)}.stat strong{display:block;font-size:28px;margin-top:6px}
.layout{display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start}.panel h2{margin:0 0 17px;font-size:19px}
label{display:block;font-weight:650;font-size:13px;margin:13px 0 6px}
input,select{width:100%;padding:11px 12px;border:1px solid #d4dbe6;border-radius:9px;font:inherit;background:white}
input:focus,select:focus{outline:2px solid #f4b6b0;border-color:var(--red)}
.qty-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}.qty-grid label{font-size:12px}
button,.btn{border:0;border-radius:9px;padding:11px 14px;background:var(--red);color:white;font-weight:700;cursor:pointer;font:inherit;text-decoration:none;display:inline-block}
button:hover,.btn:hover{background:var(--red2)}.submit{width:100%;margin-top:17px}
.alert{padding:12px 14px;border-radius:10px;margin-bottom:16px}.success{background:#e8f7ee;color:#17643f}.danger{background:#fff0ef;color:#9d2119}
.filters{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:15px}.filters a{padding:7px 11px;border:1px solid var(--line);border-radius:20px;text-decoration:none;color:var(--ink);font-size:13px}.filters a.active{background:#fff0ef;color:var(--red);border-color:#f0b4af}
.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:13px}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid var(--line);vertical-align:top}th{color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.status{display:inline-block;padding:5px 9px;border-radius:20px;font-size:11px;font-weight:750;white-space:nowrap}.preparando{background:#fff2d7;color:#8b5a00}.despachado{background:#e4f7ec;color:#17643f}
.actions{display:flex;gap:6px;align-items:center;min-width:165px}.actions select{padding:7px;min-width:105px;font-size:12px}.actions button{padding:7px 9px;font-size:12px}.delete{background:#f0f2f6;color:#5b6472}.delete:hover{background:#e0e4eb}
.empty{text-align:center;color:var(--muted);padding:30px 10px}.muted{color:var(--muted)}
@media(max-width:850px){.layout{grid-template-columns:1fr}.stats{grid-template-columns:1fr 1fr}.stats .stat:last-child{grid-column:span 2}}
@media(max-width:500px){.stats{grid-template-columns:1fr 1fr}.stat{padding:14px}.stat strong{font-size:23px}header .wrap{align-items:flex-start}h1{font-size:22px}.qty-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<header><div class="wrap"><div class="logo">🍔</div><div><h1>Gestión de pedidos</h1><p>Panel de administración de la hamburguesería</p></div></div></header>
<main class="wrap">
<?php if ($mensaje): ?><div class="alert success"><?=h($mensaje)?></div><?php endif; ?>
<?php if ($error): ?><div class="alert danger"><?=h($error)?></div><?php endif; ?>
<section class="stats">
  <div class="stat"><small>Pedidos mostrados</small><strong><?=number_format($totalPedidos)?></strong></div>
  <div class="stat"><small>Hamburguesas en pedidos mostrados</small><strong><?=number_format($totalUnidades)?></strong></div>
  <div class="stat"><small>En preparación</small><strong><?php $n=0; foreach($pedidos as $p) if($p['estado']==='preparando') $n++; echo number_format($n); ?></strong></div>
</section>
<div class="layout">
<section class="panel">
<h2>＋ Registrar pedido</h2>
<form method="post" action="index.php">
<input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>">
<input type="hidden" name="accion" value="crear">
<label for="direccion">Dirección de entrega</label>
<input id="direccion" name="direccion" maxlength="255" placeholder="Ej. Calle 10 # 20-30" required>
<div class="qty-grid">
<div><label for="sencilla">Sencilla</label><input id="sencilla" type="number" name="cantidad_sencilla" min="0" max="10000" value="0" required></div>
<div><label for="doble">Doble</label><input id="doble" type="number" name="cantidad_doble" min="0" max="10000" value="0" required></div>
<div><label for="mixta">Mixta</label><input id="mixta" type="number" name="cantidad_mixta" min="0" max="10000" value="0" required></div>
</div>
<button class="submit" type="submit">Guardar pedido</button>
<p class="muted" style="font-size:12px;margin-bottom:0">El pedido debe incluir al menos una hamburguesa. El estado inicial será “Preparando”.</p>
</form>
</section>
<section class="panel">
<h2>Pedidos registrados</h2>
<nav class="filters">
<a class="<?= $filtro===''?'active':''?>" href="index.php">Todos</a>
<a class="<?=$filtro==='preparando'?'active':''?>" href="?estado=preparando">Preparando</a>
<a class="<?=$filtro==='despachado'?'active':''?>" href="?estado=despachado">Despachados</a>
</nav>
<?php if (!$pedidos): ?><div class="empty">🍟<br><strong>No hay pedidos para mostrar</strong><br><span>Registra un pedido usando el formulario.</span></div>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>Pedido</th><th>Dirección</th><th>Cantidades</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($pedidos as $p): ?>
<tr>
<td><strong>#<?=h($p['id'])?></strong><br><span class="muted"><?=h(date('d/m/Y H:i', strtotime($p['creado_en'])))?></span></td>
<td><?=h($p['direccion'])?></td>
<td>Sencilla: <strong><?=h($p['cantidad_sencilla'])?></strong><br>Doble: <strong><?=h($p['cantidad_doble'])?></strong><br>Mixta: <strong><?=h($p['cantidad_mixta'])?></strong></td>
<td><span class="status <?=h($p['estado'])?>"><?= $p['estado']==='preparando'?'Preparando':'Despachado' ?></span></td>
<td><div class="actions">
<form method="post" action="index.php">
<input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>">
<input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?=h($p['id'])?>">
<select name="estado" aria-label="Estado del pedido #<?=h($p['id'])?>">
<option value="preparando" <?=$p['estado']==='preparando'?'selected':''?>>Preparando</option>
<option value="despachado" <?=$p['estado']==='despachado'?'selected':''?>>Despachado</option>
</select><button type="submit">Actualizar</button>
</form>
<form method="post" action="index.php" onsubmit="return confirm('¿Seguro que deseas eliminar el pedido #<?=h($p['id'])?>?');">
<input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>">
<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=h($p['id'])?>">
<button class="delete" type="submit" title="Eliminar pedido">✕</button>
</form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
</div>
</main>
</body>
</html>
