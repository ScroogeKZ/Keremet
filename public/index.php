<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Хром-KZ Логистика - Грузоперевозки по Казахстану</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1e40af',
                        'primary-dark': '#1e3a8a',
                        'secondary': '#f59e0b',
                        'accent': '#10b981'
                    }
                }
            }
        }
    </script>
    <style>
        .hero-bg {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 50%, #3730a3 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .gradient-text {
            background: linear-gradient(135deg, #1e40af, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-sm shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-br from-primary to-primary-dark p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6 md:h-8 md:w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold gradient-text">Хром-KZ</h1>
                        <p class="text-xs md:text-sm text-gray-600 font-medium">Логистика</p>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-600 hover:text-primary p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                <!-- Desktop menu -->
                <div class="hidden md:flex space-x-4">
                    <a href="/astana.php" class="bg-gradient-to-r from-primary to-primary-dark text-white px-6 py-3 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                        Заказать доставку
                    </a>
                    <a href="/client/login.php" class="bg-green-600 text-white px-4 py-3 rounded-xl font-medium hover:bg-green-700 transition-all duration-200">
                        Личный кабинет
                    </a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 transition-all duration-200">
                        Вход в систему
                    </a>
                </div>
            </div>
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 pt-4 pb-4">
                <div class="flex flex-col space-y-3">
                    <a href="/astana.php" class="bg-gradient-to-r from-primary to-primary-dark text-white px-4 py-3 rounded-xl font-semibold text-center">
                        Заказать доставку
                    </a>
                    <a href="/client/login.php" class="bg-green-600 text-white px-4 py-3 rounded-xl font-medium text-center hover:bg-green-700">
                        Личный кабинет
                    </a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-4 py-3 rounded-xl hover:bg-gray-100 text-center">
                        Вход в систему
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-bg text-white py-12 md:py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <div class="inline-flex items-center bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full mb-8">
                <span class="w-2 h-2 bg-accent rounded-full mr-2"></span>
                <span class="text-sm font-medium">Надёжная логистика с 2020 года</span>
            </div>
            
            <h1 class="text-3xl md:text-5xl lg:text-7xl font-bold mb-6 md:mb-8 leading-tight">
                Система управления 
                <span class="text-secondary">заказами</span>
            </h1>
            <p class="text-lg md:text-xl lg:text-2xl mb-8 md:mb-12 max-w-4xl mx-auto leading-relaxed opacity-90 px-4">
                Внутренний инструмент для создания и управления заказами на доставку. 
                Упрощенный процесс оформления заявок с автоматическим уведомлением менеджеров.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 md:gap-6 justify-center items-center mb-12 md:mb-16 px-4">
                <a href="/astana.php" class="group bg-white text-primary px-6 md:px-10 py-3 md:py-4 rounded-2xl font-bold text-base md:text-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300 w-full sm:w-auto text-center">
                    <span class="flex items-center justify-center">
                        📋 Создать заказ по Астане
                    </span>
                </a>
                <a href="/regional.php" class="group border-2 border-white/50 text-white px-6 md:px-10 py-3 md:py-4 rounded-2xl font-bold text-base md:text-lg hover:bg-white hover:text-primary transition-all duration-300 w-full sm:w-auto text-center">
                    <span class="flex items-center justify-center">
                        🗂️ Создать межгородской заказ
                    </span>
                </a>
                <a href="/tracking.php" class="group border-2 border-secondary/50 text-secondary px-6 md:px-10 py-3 md:py-4 rounded-2xl font-bold text-base md:text-lg hover:bg-secondary hover:text-white transition-all duration-300 w-full sm:w-auto text-center">
                    <span class="flex items-center justify-center">
                        📍 Отследить заказ
                    </span>
                </a>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 md:gap-8 max-w-2xl mx-auto px-4">
                <div class="text-center">
                    <div class="text-2xl md:text-3xl font-bold text-secondary mb-2">2м</div>
                    <div class="text-xs md:text-sm opacity-80">Среднее время обработки</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl md:text-3xl font-bold text-secondary mb-2">24/7</div>
                    <div class="text-xs md:text-sm opacity-80">Доступность системы</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl md:text-3xl font-bold text-secondary mb-2">100%</div>
                    <div class="text-xs md:text-sm opacity-80">Автоматических уведомлений</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-12 md:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-8 md:mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="gradient-text">Типы заказов</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Выберите нужный тип заказа для оформления заявки на доставку
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 md:gap-12">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 p-6 md:p-10 rounded-3xl shadow-xl card-hover border border-blue-200">
                    <div class="bg-primary text-white w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        🚚
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">Заказы по Астане</h3>
                    <p class="text-gray-600 mb-8 leading-relaxed">
                        Создание заявок на доставку в пределах города. 
                        Автоматическое уведомление курьерской службы и отдела логистики.
                    </p>
                    <ul class="space-y-3 mb-8 text-gray-700">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-accent rounded-full mr-3"></span>
                            Мгновенное создание заявки
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-accent rounded-full mr-3"></span>
                            Автоматические уведомления
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-accent rounded-full mr-3"></span>
                            Отслеживание статуса
                        </li>
                    </ul>
                    <a href="/astana.php" class="inline-block bg-primary text-white px-8 py-4 rounded-xl font-semibold hover:bg-primary-dark transform hover:scale-105 transition-all duration-200">
                        Создать заказ
                    </a>
                </div>
                
                <div class="bg-gradient-to-br from-orange-50 to-amber-100 p-6 md:p-10 rounded-3xl shadow-xl card-hover border border-orange-200">
                    <div class="bg-secondary text-white w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        🌍
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">Межгородские заказы</h3>
                    <p class="text-gray-600 mb-8 leading-relaxed">
                        Создание заявок на доставку между городами. 
                        Расширенная форма с дополнительными параметрами для межрегиональных отправлений.
                    </p>
                    <ul class="space-y-3 mb-8 text-gray-700">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-accent rounded-full mr-3"></span>
                            Выбор городов отправления и назначения
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-accent rounded-full mr-3"></span>
                            Настройка способа доставки
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-accent rounded-full mr-3"></span>
                            Указание желаемых сроков
                        </li>
                    </ul>
                    <a href="/regional.php" class="inline-block bg-secondary text-white px-8 py-4 rounded-xl font-semibold hover:bg-amber-600 transform hover:scale-105 transition-all duration-200">
                        Создать заказ
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-gradient-to-br from-gray-50 to-blue-50 py-12 md:py-20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    <span class="gradient-text">Преимущества системы</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Возможности внутрикорпоративного инструмента управления заказами
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-lg card-hover text-center">
                    <div class="bg-gradient-to-br from-primary to-primary-dark text-white w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 mx-auto">
                        ⚡
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">Простота использования</h3>
                    <p class="text-gray-600 leading-relaxed">Интуитивный интерфейс для быстрого создания заказов. Все необходимые поля в одной форме</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-lg card-hover text-center">
                    <div class="bg-gradient-to-br from-accent to-emerald-600 text-white w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 mx-auto">
                        🔔
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">Автоматизация</h3>
                    <p class="text-gray-600 leading-relaxed">Автоматические уведомления ответственным сотрудникам через Telegram при создании заказа</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-lg card-hover text-center">
                    <div class="bg-gradient-to-br from-secondary to-orange-600 text-white w-20 h-20 rounded-2xl flex items-center justify-center text-4xl mb-6 mx-auto">
                        📊
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">Управление</h3>
                    <p class="text-gray-600 leading-relaxed">Административная панель для просмотра всех заказов и изменения их статуса в реальном времени</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid lg:grid-cols-3 gap-12 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="bg-gradient-to-br from-primary to-primary-dark p-2 rounded-lg">
                            <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                        </div>
                        <span class="text-2xl font-bold">Хром-KZ Логистика</span>
                    </div>
                    <p class="text-gray-300 leading-relaxed">
                        Надёжный партнёр в сфере грузоперевозок по Казахстану. 
                        Более 5 лет опыта успешной работы в логистической сфере.
                    </p>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-6">Функции системы</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li><a href="/astana.php" class="hover:text-secondary transition-colors">Создание заказов по Астане</a></li>
                        <li><a href="/regional.php" class="hover:text-secondary transition-colors">Создание межгородских заказов</a></li>
                        <li><a href="/admin/login.php" class="hover:text-secondary transition-colors">Административная панель</a></li>
                        <li><span class="text-gray-400">Telegram-уведомления</span></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-6">Связаться с нами</h3>
                    <div class="space-y-3 text-gray-300">
                        <p>📞 +7 (7172) XX-XX-XX</p>
                        <p>📧 info@khrom-kz.com</p>
                        <p>📍 г. Астана, ул. Примерная, 123</p>
                        <p>🕒 Пн-Пт: 8:00-20:00, Сб-Вс: 9:00-18:00</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Хром-KZ Логистика. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            
            if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>