<?php
/**
 * @file index.php
 * @brief Страница отображения всех рецептов с пагинацией.
 *
 * Загружает все рецепты из файла storage/recipes.txt,
 * отображает их постранично по 5 штук на страницу.
 *
 * Поддерживает параметр GET `page`, например:
 * - /recipe/index.php?page=1
 * - /recipe/index.php?page=2
 *
 * Выводит основную информацию по каждому рецепту:
 * - название, категория, описание, ингредиенты, шаги, теги, дата
 */

$filepath = __DIR__ . '/../../storage/recipes.txt';
$recipes = [];

if (file_exists($filepath)) {
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recipes = array_reverse(array_map('json_decode', $lines));
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;
$total = count($recipes);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

$currentRecipes = array_slice($recipes, $offset, $perPage);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Все рецепты</title>
</head>
<body>
    <h1>Все рецепты (страница <?= $page ?>)</h1>

    <?php if (empty($currentRecipes)): ?>
        <p>На этой странице нет рецептов</p>
    <?php else: ?>
        <?php foreach ($currentRecipes as $recipe): ?>
            <div>
                <h3><?= htmlspecialchars($recipe->title) ?></h3>
                <p><strong>Категория:</strong> <?= htmlspecialchars($recipe->category) ?></p>
                <p><strong>Ингредиенты:</strong><br><?= nl2br(htmlspecialchars($recipe->ingredients)) ?></p>
                <p><strong>Описание:</strong><br><?= nl2br(htmlspecialchars($recipe->description)) ?></p>
                <p><strong>Шаги:</strong></p>
                <ol>
                    <?php foreach ($recipe->steps as $step): ?>
                        <li><?= htmlspecialchars($step) ?></li>
                    <?php endforeach; ?>
                </ol>
                <p><strong>Теги:</strong> <?= implode(', ', array_map('htmlspecialchars', $recipe->tags)) ?></p>
                <p><em>Добавлено: <?= $recipe->created_at ?></em></p>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Навигация -->
    <div>
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Назад</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <strong>[<?= $i ?>]</strong>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Вперёд &raquo;</a>
        <?php endif; ?>
    </div>

    <p><a href="/index.php">На главную</a></p>
</body>
</html>
