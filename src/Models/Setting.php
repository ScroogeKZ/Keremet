<?php

namespace App\Models;

use Exception;

class Setting
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    /**
     * Get setting value by key
     */
    public function get($key, $default = null)
    {
        $sql = "SELECT value, type FROM settings WHERE key = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return $default;
        }
        
        return $this->convertValue($result['value'], $result['type']);
    }

    /**
     * Set setting value
     */
    public function set($key, $value, $type = 'string', $category = 'system', $description = '', $userId = null)
    {
        $sql = "INSERT INTO settings (key, value, type, category, created_at, updated_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT (key) 
                DO UPDATE SET 
                    value = EXCLUDED.value,
                    type = EXCLUDED.type,
                    updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $key,
            $this->prepareValue($value, $type),
            $type,
            $category
        ]);
    }

    /**
     * Get all settings by category
     */
    public function getByCategory($category)
    {
        $sql = "SELECT * FROM settings WHERE category = ? ORDER BY key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category]);
        $results = $stmt->fetchAll();
        
        $settings = [];
        foreach ($results as $result) {
            $settings[$result['key']] = [
                'value' => $this->convertValue($result['value'], $result['type']),
                'type' => $result['type'],
                'created_at' => $result['created_at'],
                'updated_at' => $result['updated_at']
            ];
        }
        
        return $settings;
    }

    /**
     * Get all settings
     */
    public function getAll()
    {
        $sql = "SELECT * FROM settings ORDER BY category, key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $settings = [];
        foreach ($results as $result) {
            $settings[$result['category']][$result['key']] = [
                'value' => $this->convertValue($result['value'], $result['type']),
                'type' => $result['type'],
                'created_at' => $result['created_at'],
                'updated_at' => $result['updated_at']
            ];
        }
        
        return $settings;
    }

    /**
     * Update multiple settings at once
     */
    public function updateBatch($settings, $userId = null)
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($settings as $key => $data) {
                $value = $data['value'] ?? $data;
                $type = $data['type'] ?? 'string';
                $category = $data['category'] ?? 'system';
                $description = $data['description'] ?? '';
                
                $this->set($key, $value, $type, $category, $description, $userId);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete setting
     */
    public function delete($key)
    {
        $sql = "DELETE FROM settings WHERE setting_key = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$key]);
    }

    /**
     * Convert database value to proper PHP type
     */
    private function convertValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value === 'true' || $value === '1' || $value === 1;
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Prepare value for database storage
     */
    private function prepareValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
                return (string) (int) $value;
            case 'json':
                return json_encode($value);
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Get settings for forms
     */
    public function getFormSettings()
    {
        $all = $this->getAll();
        
        return [
            'profile' => $all['profile'] ?? [],
            'notifications' => $all['notifications'] ?? [],
            'system' => $all['system'] ?? [],
            'security' => $all['security'] ?? [],
            'pricing' => $all['pricing'] ?? []
        ];
    }

    /**
     * Test Telegram connection
     */
    public function testTelegramConnection()
    {
        $token = $this->get('telegram_bot_token');
        $chatId = $this->get('telegram_chat_id');
        
        if (empty($token) || empty($chatId)) {
            return ['success' => false, 'message' => 'Не настроены токен или Chat ID'];
        }
        
        try {
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $data = [
                'chat_id' => $chatId,
                'text' => '🔧 Тест подключения к Telegram боту - ' . date('Y-m-d H:i:s')
            ];
            
            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $response = json_decode($result, true);
            
            if ($response && $response['ok']) {
                return ['success' => true, 'message' => 'Подключение успешно установлено'];
            } else {
                return ['success' => false, 'message' => 'Ошибка отправки: ' . ($response['description'] ?? 'Неизвестная ошибка')];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка подключения: ' . $e->getMessage()];
        }
    }
}