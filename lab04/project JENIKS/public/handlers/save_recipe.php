<?php
/**
 * @file save_recipe.php
 * @brief Обработчик формы добавления рецепта.
 *
 * Получает данные из формы (POST), выполняет:
 * - фильтрацию и очистку данных;
 * - валидацию обязательных полей;
 * - сохранение валидных данных в файл storage/recipes.txt в формате JSON;
 * - сохранение ошибок и возврат на форму при неудаче;
 * - перенаправление на главную при успехе.
 *
 * Использует:
 * - sanitize() и validate() из helpers.php
 * - session для передачи ошибок и старых значений
 */

ob_start(); 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $ingredients = sanitize($_POST['ingredients'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $tags = $_POST['tags'] ?? [];

    $stepsRaw = $_POST['steps'] ?? [];
    $steps = array_filter(array_map('sanitize', $stepsRaw));

    $formData = [
        'title' => $title,
        'category' => $category,
        'ingredients' => $ingredients,
        'description' => $description,
        'tags' => $tags,
        'steps' => $steps,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $errors = validate($formData);

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $_POST;
        header('Location: ../../public/recipe/create.php');
        exit;
    }

    $line = json_encode($formData, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    
    file_put_contents(__DIR__ . '/../../storage/recipes.txt', $line, FILE_APPEND);

    header('Location: ../../public/index.php');
    exit;
}
