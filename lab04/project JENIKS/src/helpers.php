<?php
/**
 * @file helpers.php
 * @brief Вспомогательные функции для обработки и валидации данных рецепта.
 *
 * Содержит:
 * - sanitize() — фильтрация пользовательского ввода
 * - validate() — базовая валидация данных формы рецепта
 *
 * Используется в: save_recipe.php
 */

 
/**
 * Очищает строку от лишних символов
 *
 * @param string $data
 * @return string
 */
function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Проверяет обязательные поля на заполненность
 *
 * @param array $data
 * @return array массив ошибок
 */
function validate(array $data): array {
    $errors = [];

    if (empty($data['title'])) {
        $errors['title'] = 'Введите название рецепта';
    }

    if (empty($data['category'])) {
        $errors['category'] = 'Выберите категорию';
    }

    if (empty($data['ingredients'])) {
        $errors['ingredients'] = 'Введите ингредиенты';
    }

    if (empty($data['description'])) {
        $errors['description'] = 'Введите описание рецепта';
    }

    if (empty($data['steps']) || !is_array($data['steps']) || count(array_filter($data['steps'])) === 0) {
        $errors['steps'] = 'Добавьте хотя бы один шаг приготовления';
    }

    return $errors;
}
