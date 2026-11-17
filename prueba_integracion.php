<?php

require_once 'MenuController.php';

$pdo = new PDO('mysql:host=localhost;dbname=nutricampus;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$controlador = new MenuController(new ValidacionMenuService(), new MenuRepository($pdo));

echo "=== CASO VALIDO ===\n";
print_r($controlador->publicarMenuDiario('Encargado', '2026-10-30', 'Almuerzo', 'Pesque de quinua con guiso de res', 'Quinua, carne de res, queso, leche, arroz'));

echo "\n=== CASO INVALIDO ===\n";
print_r($controlador->publicarMenuDiario('Estudiante', '2026-10-30', 'Almuerzo', 'Trucha frita', 'Trucha, papa, ensalada'));