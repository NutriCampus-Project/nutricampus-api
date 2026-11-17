<?php

class MenuRepository {
    public function __construct(private PDO $pdo) {}

    public function guardar(array $menu): int {
        $sql = "INSERT INTO menus (fecha, turno, plato_principal, ingredientes, publicado_por)
                VALUES (:fecha, :turno, :plato_principal, :ingredientes, :publicado_por)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($menu);
        return (int) $this->pdo->lastInsertId();
    }
}