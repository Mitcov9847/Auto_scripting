<?php
/**
 * @file index.php
 * @brief Главная страница каталога рецептов.
 *
 * Отображает два последних добавленных рецепта из файла storage/recipes.txt.
 * Если рецептов нет, выводит сообщение.
 * Использует json_decode для преобразования строк JSON в объекты.
 * Все данные экранируются через htmlspecialchars().
 */

$filepath = __DIR__ . '/../storage/recipes.txt';
$recipes = [];

if (file_exists($filepath)) {
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recipes = array_reverse(array_map('json_decode', $lines));
    $latest = array_slice($recipes, 0, 2);
} else {
    $latest = [];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
</head>
<body>
    <h1>Последние рецепты</h1>

    <?php if (count($latest) === 0): ?>
        <p>Пока нет рецептов</p>
    <?php else: ?>
        <?php foreach ($latest as $recipe): ?>
            <div>
                <h3><?= htmlspecialchars($recipe->title) ?></h3>
                <p><strong>Категория:</strong> <?= htmlspecialchars($recipe->category) ?></p>
                <p><strong>Описание:</strong> <?= nl2br(htmlspecialchars($recipe->description)) ?></p>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="/recipe/create.php">Добавить новый рецепт</a>
    <br>
    <a href="/recipe/index.php">Все рецепты</a>
</body>
</html>
