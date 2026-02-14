<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE email = 'admin@bhsac.com'");
    $stmt->execute([$hash]);

    if ($stmt->rowCount() > 0) {
        echo "[OK] Senha de admin@bhsac.com atualizada com sucesso.\n";
    } else {
        echo "[INFO] Nenhuma alteração feita (provavelmente a senha já é a mesma).\n";
    }

} catch (Exception $e) {
    echo "[ERRO] Falha ao atualizar senha: " . $e->getMessage() . "\n";
}
?>