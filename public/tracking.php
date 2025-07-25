<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отслеживание заказа - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tracking-step {
            position: relative;
            padding-left: 2rem;
        }
        .tracking-step::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 1.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: #e5e7eb;
        }
        .tracking-step.completed::before {
            background: #10b981;
        }
        .tracking-step.active::before {
            background: #3b82f6;
            animation: pulse 2s infinite;
        }
        .tracking-step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 2.5rem;
            width: 2px;
            height: 3rem;
            background: #e5e7eb;
        }
        .tracking-step.completed:not(:last-child)::after {
            background: #10b981;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .hero-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Хром-KZ</h1>
                        <p class="text-sm text-gray-600">Отслеживание заказов</p>
                    </div>
                </div>
                <a href="/" class="text-gray-600 hover:text-gray-900 font-medium">На главную</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-bg text-white py-12">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Отслеживание заказа</h1>
            <p class="text-lg opacity-90 mb-8">Введите номер заказа или контактный телефон для отслеживания</p>
            
            <!-- Search Form -->
            <div class="max-w-md mx-auto">
                <div class="relative">
                    <input type="text" 
                           id="trackingInput" 
                           placeholder="Номер заказа или телефон"
                           class="w-full px-4 py-3 rounded-lg text-gray-900 text-center text-lg font-medium">
                    <button onclick="trackOrder()" 
                            class="absolute right-2 top-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Найти
                    </button>
                </div>
                <p class="text-sm opacity-80 mt-2">Например: 123 или +7 777 123 45 67</p>
            </div>
        </div>
    </section>

    <!-- Results Section -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Loading State -->
        <div id="loadingState" class="hidden text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="text-gray-600 mt-4">Поиск заказа...</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-red-800 mb-2">Заказ не найден</h3>
                <p class="text-red-600" id="errorMessage">Проверьте правильность введенных данных</p>
            </div>
        </div>

        <!-- Success State -->
        <div id="successState" class="hidden">
            <!-- Order Info Card -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900" id="orderTitle">Заказ #123456</h2>
                        <p class="text-gray-600" id="orderDate">Создан: 23.07.2025</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium" id="orderStatus">
                            В обработке
                        </span>
                        <p class="text-gray-600 text-sm mt-1" id="estimatedDelivery">ETA: 24.07.2025</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Откуда</h4>
                        <p class="text-gray-600" id="pickupAddress">Адрес забора</p>
                        <p class="text-sm text-gray-500 mt-1" id="pickupContact">Контакт отправителя</p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Куда</h4>
                        <p class="text-gray-600" id="deliveryAddress">Адрес доставки</p>
                        <p class="text-sm text-gray-500 mt-1" id="deliveryContact">Контакт получателя</p>
                    </div>
                </div>
                
                <div class="border-t pt-4 mt-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Тип груза:</span>
                            <span class="font-medium ml-1" id="cargoType">-</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Вес:</span>
                            <span class="font-medium ml-1" id="cargoWeight">-</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Стоимость:</span>
                            <span class="font-medium ml-1" id="shippingCost">-</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Трек-номер:</span>
                            <span class="font-medium ml-1" id="trackingNumber">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tracking Timeline -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">История перемещения</h3>
                <div id="trackingTimeline" class="space-y-6">
                    <!-- Timeline items will be inserted here -->
                </div>
            </div>

            <!-- Current Location Map -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Текущее местоположение</h3>
                <div class="bg-gray-100 rounded-lg h-48 flex items-center justify-center">
                    <div class="text-center text-gray-600">
                        <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p id="currentLocation">Текущее местоположение груза</p>
                        <div id="progressBar" class="w-full bg-gray-200 rounded-full h-2 mt-4">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2" id="progressText">0% выполнено</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentOrder = null;

        async function trackOrder() {
            const input = document.getElementById('trackingInput');
            const trackingNumber = input.value.trim();
            
            if (!trackingNumber) {
                showError('Введите номер заказа или телефон');
                return;
            }

            showLoading();
            
            try {
                const response = await fetch(`/api/tracking.php?tracking=${encodeURIComponent(trackingNumber)}`);
                const data = await response.json();
                
                if (data.success) {
                    currentOrder = data;
                    showOrderDetails(data);
                } else {
                    showError(data.error || 'Заказ не найден');
                }
            } catch (error) {
                showError('Ошибка при поиске заказа');
                console.error('Tracking error:', error);
            }
        }

        function showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('errorState').classList.add('hidden');
            document.getElementById('successState').classList.add('hidden');
        }

        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorState').classList.remove('hidden');
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('successState').classList.add('hidden');
        }

        function showOrderDetails(data) {
            const order = data.order;
            const statusColors = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'in_progress': 'bg-blue-100 text-blue-800',
                'completed': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800'
            };

            // Заполняем основную информацию
            document.getElementById('orderTitle').textContent = `Заказ #${order.id}`;
            document.getElementById('orderDate').textContent = `Создан: ${formatDate(order.created_at)}`;
            document.getElementById('orderStatus').textContent = order.status_ru;
            document.getElementById('orderStatus').className = `inline-block px-3 py-1 rounded-full text-sm font-medium ${statusColors[order.status] || 'bg-gray-100 text-gray-800'}`;
            document.getElementById('estimatedDelivery').textContent = `ETA: ${formatDate(order.estimated_delivery)}`;
            
            document.getElementById('pickupAddress').textContent = order.pickup_address;
            document.getElementById('deliveryAddress').textContent = order.delivery_address;
            document.getElementById('pickupContact').textContent = order.pickup_contact || 'Не указан';
            document.getElementById('deliveryContact').textContent = order.delivery_contact || 'Не указан';
            
            document.getElementById('cargoType').textContent = order.cargo_type || 'Не указан';
            document.getElementById('cargoWeight').textContent = order.weight ? `${order.weight} кг` : 'Не указан';
            document.getElementById('shippingCost').textContent = order.shipping_cost ? `${parseInt(order.shipping_cost).toLocaleString()} ₸` : 'Не указана';
            document.getElementById('trackingNumber').textContent = order.tracking_number;

            // Заполняем timeline
            renderTimeline(data.status_history);
            
            // Обновляем текущее местоположение
            document.getElementById('currentLocation').textContent = data.tracking_info.current_location;
            document.getElementById('progressText').textContent = `${data.tracking_info.progress_percentage}% выполнено`;
            document.querySelector('#progressBar > div').style.width = `${data.tracking_info.progress_percentage}%`;

            // Показываем результат
            document.getElementById('successState').classList.remove('hidden');
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('errorState').classList.add('hidden');
        }

        function renderTimeline(history) {
            const timeline = document.getElementById('trackingTimeline');
            timeline.innerHTML = '';

            history.forEach((item, index) => {
                const isCompleted = index < history.length - 1 || currentOrder.order.status !== 'pending';
                const isActive = index === history.length - 1 && currentOrder.order.status !== 'completed';
                
                const timelineItem = document.createElement('div');
                timelineItem.className = `tracking-step ${isCompleted ? 'completed' : ''} ${isActive ? 'active' : ''}`;
                
                timelineItem.innerHTML = `
                    <div class="font-medium text-gray-900">${item.status}</div>
                    <div class="text-sm text-gray-600 mt-1">${item.description}</div>
                    <div class="text-xs text-gray-500 mt-1">${formatDateTime(item.timestamp)}</div>
                `;
                
                timeline.appendChild(timelineItem);
            });
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU');
        }

        function formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('ru-RU');
        }

        // Поиск по Enter
        document.getElementById('trackingInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                trackOrder();
            }
        });

        // Автоматическое обновление статуса каждые 30 секунд
        setInterval(() => {
            if (currentOrder && currentOrder.order.status !== 'completed' && currentOrder.order.status !== 'cancelled') {
                trackOrder();
            }
        }, 30000);
    </script>
</body>
</html>