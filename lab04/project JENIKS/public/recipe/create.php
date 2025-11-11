<?php
/**
 * @file create.php
 * @brief HTML-форма для добавления рецепта.
 *
 * Используется для ввода нового рецепта пользователем.
 * После отправки форма передаёт данные на save_recipe.php.
 *
 * Функциональность:
 * - Отображение формы с полями
 * - Вывод ошибок под полями (если есть)
 * - Сохранение старых значений при ошибке валидации
 * - Динамическое добавление полей "шагов приготовления"
 *
 * Использует session для передачи ошибок и предыдущих данных.
 */

session_start();

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];

unset($_SESSION['errors'], $_SESSION['old']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить рецепт</title>
    <style>
        input, textarea, select {
            display: block;
            margin-bottom: 8px;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-top: -5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>Добавить рецепт</h1>

    <form action="/handlers/save_recipe.php" method="post">
        <label>Название рецепта:
            <input type="text" name="title" value="<?= htmlspecialchars($old['title'] ?? '') ?>" required>
            <?php if (isset($errors['title'])): ?><div class="error"><?= $errors['title'] ?></div><?php endif; ?>
        </label>

        <label>Категория:
            <select name="category" required>
                <option value="">Выберите</option>
                <?php foreach (['Супы', 'Салаты', 'Десерты'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= ($old['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category'])): ?><div class="error"><?= $errors['category'] ?></div><?php endif; ?>
        </label>

        <label>Ингредиенты:
            <textarea name="ingredients" required><?= htmlspecialchars($old['ingredients'] ?? '') ?></textarea>
            <?php if (isset($errors['ingredients'])): ?><div class="error"><?= $errors['ingredients'] ?></div><?php endif; ?>
        </label>

        <label>Описание:
            <textarea name="description" required><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
            <?php if (isset($errors['description'])): ?><div class="error"><?= $errors['description'] ?></div><?php endif; ?>
        </label>

        <label>Теги (удерживайте Ctrl для выбора нескольких):<br>
            <select name="tags[]" multiple size="4">
                <?php foreach (['Веган', 'Безглютеновый', 'Праздничный', 'Быстрый'] as $tag): ?>
                    <option value="<?= $tag ?>" <?= in_array($tag, $old['tags'] ?? []) ? 'selected' : '' ?>><?= $tag ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Шаги приготовления:</label><br>
        <div id="steps">
            <?php
            $steps = $old['steps'] ?? [''];
            foreach ($steps as $step):
            ?>
                <input type="text" name="steps[]" value="<?= htmlspecialchars($step) ?>" placeholder="Введите шаг приготовления" required><br>
            <?php endforeach; ?>
        </div>
        <?php if (isset($errors['steps'])): ?><div class="error"><?= $errors['steps'] ?></div><?php endif; ?>

        <button type="button" id="add-step">Добавить шаг</button><br><br>
        <button type="submit">Отправить</button>
    </form>

    <script>
        /**
         * Добавляет новое поле для шага приготовления.
         * Поле имеет name="steps[]" и отображается под остальными.
         */
        function addStep() {
            const steps = document.getElementById('steps');
            steps.insertAdjacentHTML('beforeend',
                '<input type="text" name="steps[]" placeholder="Введите шаг приготовления" required><br>');
        }

        /**
         * При загрузке страницы:
         * - Назначает обработчик на кнопку "Добавить шаг"
         * - Не добавляет шаг автоматически, т.к. шаги выводятся из PHP
         */
        window.onload = function () {
            const addBtn = document.getElementById('add-step');
            addBtn.onclick = addStep;
        };
    </script>
</body>
</html>
