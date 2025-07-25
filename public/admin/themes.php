<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;

Auth::requireAuth();

// Обработка смены темы
if ($_POST && isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    if (in_array($theme, ['light', 'dark', 'auto'])) {
        setcookie('user_theme', $theme, time() + (86400 * 365), '/'); // На год
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$currentTheme = $_COOKIE['user_theme'] ?? 'light';
$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru" class="<?php echo $currentTheme === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки темы - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#1a1a1a',
                            surface: '#2a2a2a',
                            border: '#3a3a3a',
                            text: '#e5e5e5',
                            muted: '#a0a0a0'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Темная тема */
        .dark {
            color-scheme: dark;
        }
        
        .dark body {
            background-color: #1a1a1a;
            color: #e5e5e5;
        }
        
        .dark .bg-white {
            background-color: #2a2a2a !important;
        }
        
        .dark .border-gray-200 {
            border-color: #3a3a3a !important;
        }
        
        .dark .text-gray-900 {
            color: #e5e5e5 !important;
        }
        
        .dark .text-gray-600 {
            color: #a0a0a0 !important;
        }
        
        .dark .text-gray-500 {
            color: #808080 !important;
        }
        
        .dark .bg-gray-50 {
            background-color: #1a1a1a !important;
        }
        
        .dark .bg-gray-100 {
            background-color: #2a2a2a !important;
        }
        
        .dark .hover\\:bg-gray-50:hover {
            background-color: #2a2a2a !important;
        }
        
        .dark input, .dark select, .dark textarea {
            background-color: #2a2a2a !important;
            border-color: #3a3a3a !important;
            color: #e5e5e5 !important;
        }
        
        .dark input:focus, .dark select:focus, .dark textarea:focus {
            border-color: #4a4a4a !important;
        }
        
        /* Автоматическая тема */
        @media (prefers-color-scheme: dark) {
            .auto-theme {
                color-scheme: dark;
            }
            
            .auto-theme body {
                background-color: #1a1a1a;
                color: #e5e5e5;
            }
        }
    </style>
</head>
<body class="<?php echo $currentTheme === 'auto' ? 'auto-theme' : ''; ?>">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-dark-surface border-b border-gray-200 dark:border-dark-border">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <h1 class="text-lg font-medium text-gray-900 dark:text-dark-text">Настройки темы</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 dark:text-dark-muted hover:text-gray-900 dark:hover:text-dark-text">Главная</a>
                    <a href="/admin/panel.php" class="text-sm text-gray-600 dark:text-dark-muted hover:text-gray-900 dark:hover:text-dark-text">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 dark:text-dark-muted hover:text-gray-900 dark:hover:text-dark-text">Дашборд</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 dark:text-dark-muted hover:text-gray-900 dark:hover:text-dark-text">Пользователи</a>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm text-gray-700 dark:text-dark-text"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="/admin/logout.php" class="text-sm text-red-600 hover:text-red-800">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-6">
        <!-- Выбор темы -->
        <div class="bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-dark-text mb-4">Выберите тему оформления</h2>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Светлая тема -->
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="light" <?php echo $currentTheme === 'light' ? 'checked' : ''; ?>
                               class="sr-only" onchange="this.form.submit()">
                        <div class="border-2 border-gray-300 dark:border-dark-border p-4 rounded-lg <?php echo $currentTheme === 'light' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                            <div class="bg-white border border-gray-200 rounded p-3 mb-3">
                                <div class="h-2 bg-gray-100 rounded mb-2"></div>
                                <div class="h-1 bg-gray-300 rounded mb-1"></div>
                                <div class="h-1 bg-gray-300 rounded w-3/4"></div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium text-gray-900 dark:text-dark-text">Светлая тема</div>
                                <div class="text-sm text-gray-500 dark:text-dark-muted">Классическое светлое оформление</div>
                            </div>
                        </div>
                    </label>

                    <!-- Темная тема -->
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="dark" <?php echo $currentTheme === 'dark' ? 'checked' : ''; ?>
                               class="sr-only" onchange="this.form.submit()">
                        <div class="border-2 border-gray-300 dark:border-dark-border p-4 rounded-lg <?php echo $currentTheme === 'dark' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                            <div class="bg-gray-800 border border-gray-700 rounded p-3 mb-3">
                                <div class="h-2 bg-gray-700 rounded mb-2"></div>
                                <div class="h-1 bg-gray-600 rounded mb-1"></div>
                                <div class="h-1 bg-gray-600 rounded w-3/4"></div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium text-gray-900 dark:text-dark-text">Темная тема</div>
                                <div class="text-sm text-gray-500 dark:text-dark-muted">Удобная для работы в темное время</div>
                            </div>
                        </div>
                    </label>

                    <!-- Автоматическая тема -->
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="auto" <?php echo $currentTheme === 'auto' ? 'checked' : ''; ?>
                               class="sr-only" onchange="this.form.submit()">
                        <div class="border-2 border-gray-300 dark:border-dark-border p-4 rounded-lg <?php echo $currentTheme === 'auto' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                            <div class="flex rounded overflow-hidden mb-3">
                                <div class="bg-white border-r border-gray-200 flex-1 p-2">
                                    <div class="h-1 bg-gray-300 rounded mb-1"></div>
                                    <div class="h-1 bg-gray-300 rounded w-1/2"></div>
                                </div>
                                <div class="bg-gray-800 flex-1 p-2">
                                    <div class="h-1 bg-gray-600 rounded mb-1"></div>
                                    <div class="h-1 bg-gray-600 rounded w-1/2"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium text-gray-900 dark:text-dark-text">Автоматически</div>
                                <div class="text-sm text-gray-500 dark:text-dark-muted">Следует настройкам системы</div>
                            </div>
                        </div>
                    </label>
                </div>
            </form>
        </div>

        <!-- Преимущества тем -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border p-6">
                <h3 class="font-medium text-gray-900 dark:text-dark-text mb-3">Светлая тема</h3>
                <ul class="text-sm text-gray-600 dark:text-dark-muted space-y-2">
                    <li>• Классическое оформление</li>
                    <li>• Лучше для работы днем</li>
                    <li>• Высокая контрастность</li>
                    <li>• Знакомый интерфейс</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border p-6">
                <h3 class="font-medium text-gray-900 dark:text-dark-text mb-3">Темная тема</h3>
                <ul class="text-sm text-gray-600 dark:text-dark-muted space-y-2">
                    <li>• Снижает нагрузку на глаза</li>
                    <li>• Экономит заряд батареи</li>
                    <li>• Лучше для работы вечером</li>
                    <li>• Современный дизайн</li>
                </ul>
            </div>
        </div>

        <!-- Дополнительные настройки -->
        <div class="bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border p-6 mt-6">
            <h3 class="font-medium text-gray-900 dark:text-dark-text mb-4">Дополнительные настройки</h3>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-dark-text">Сохранить предпочтения</div>
                        <div class="text-sm text-gray-500 dark:text-dark-muted">Запомнить выбранную тему для следующих посещений</div>
                    </div>
                    <div class="text-green-600">✓ Включено</div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-dark-text">Анимации переходов</div>
                        <div class="text-sm text-gray-500 dark:text-dark-muted">Плавные переходы между темами</div>
                    </div>
                    <div class="text-green-600">✓ Включено</div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-dark-text">Синхронизация с системой</div>
                        <div class="text-sm text-gray-500 dark:text-dark-muted">Автоматическое переключение в режиме "Автоматически"</div>
                    </div>
                    <div class="text-green-600">✓ Включено</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Автоматическое переключение темы
        if (localStorage.getItem('theme') === 'auto') {
            const darkMode = window.matchMedia('(prefers-color-scheme: dark)');
            
            function updateTheme() {
                if (darkMode.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            
            darkMode.addListener(updateTheme);
            updateTheme();
        }
    </script>
</body>
</html>